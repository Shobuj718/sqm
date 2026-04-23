<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;


class TicketController extends Controller
{
    /**
     * Display a listing of all tickets.
     */
    public function index(Request $request): View
    {

        if (auth()->check() && auth()->user()->hasRole('agent')) {
            $query = Ticket::with(['facebookPage', 'assignedAgent'])
                ->where('assigned_to', auth()->user()->id)
                ->orderBy('created_at', 'desc');
        } else {
            $query = Ticket::with(['facebookPage', 'assignedAgent'])
                ->orderBy('created_at', 'desc');
        }

        $summaryTotal = (clone $query)->count();
        $summaryOpen = (clone $query)->where('status', 'open')->count();
        $summaryInProgress = (clone $query)->where('status', 'in_progress')->count();
        $summaryResolved = (clone $query)->where('status', 'resolved')->count();

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned agent
        if ($request->filled('assigned_to') && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Search by customer name or ticket ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_facebook_id', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }



        $tickets = $query->paginate(15);
        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager']);
        })->get();

        return view('admin.tickets.index', compact('tickets', 'agents', 'summaryTotal', 'summaryOpen', 'summaryInProgress', 'summaryResolved'));
    }

    /**
     * Display the specified ticket with full conversation history.
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load(['messages', 'facebookPage', 'assignedAgent', 'logs']);

        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['agent']);
        })->get();

        return view('admin.tickets.show', compact('ticket', 'agents'));
    }

    /**
     * Update ticket status or assignment.
     */
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'agent_message' => 'nullable|string|min:1',
        ]);

        // Store old values for logging
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;
        $oldAssignedTo = $ticket->assigned_to;
        $oldAgent = $ticket->assignedAgent;

        // Update ticket status/priority/assignment
        $updates = array_filter([
            'status' => $validated['status'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
        ], fn ($value) => $value !== null);

        if (!empty($updates)) {
            $ticket->update($updates);

            // Log status change
            if (isset($updates['status']) && $updates['status'] !== $oldStatus) {
                TicketLogService::logStatusChange($ticket, $oldStatus, $updates['status']);
            }

            // Log priority change
            if (isset($updates['priority']) && $updates['priority'] !== $oldPriority) {
                TicketLogService::logPriorityChange($ticket, $oldPriority, $updates['priority']);
            }

            // Log assignment change
            if (isset($updates['assigned_to'])) {
                $newAgent = User::find($updates['assigned_to']);
                $oldAgentName = $oldAgent?->name;
                $newAgentName = $newAgent?->name;
                TicketLogService::logAssignment($ticket, $oldAssignedTo, $updates['assigned_to'], $oldAgentName, $newAgentName);
            }
        }

        // Add agent message if provided
        if ($request->filled('agent_message')) {
            $ticket->loadMissing('facebookPage');

            $agentMessage = $validated['agent_message'];
            $ticket->addMessage(
                facebookMessageId: uniqid('agent_'),
                senderFacebookId: auth()->user()->id,
                message: $agentMessage,
                messageType: 'agent',
                channel: 'messenger'
            );

            // Log message addition
            TicketLogService::logMessageAdded($ticket, 'agent');

            if ($ticket->facebookPage?->page_token && $ticket->customer_facebook_id) {
                try {
                    Http::post('https://graph.facebook.com/v25.0/me/messages', [
                        'access_token' => $ticket->facebookPage->page_token,
                        'recipient' => ['id' => $ticket->customer_facebook_id],
                        'message' => ['text' => $agentMessage],
                    ]);


                } catch (\Exception $e) {
                    // Do not fail ticket update just because the send failed.
                    report($e);
                }
            }
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully');
    }

    /**
     * Assign ticket to an agent.
     */
    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $oldAssignedTo = $ticket->assigned_to;
        $oldAgent = $ticket->assignedAgent;
        $newAgent = User::find($validated['assigned_to']);

        $ticket->update($validated);

        // Log assignment
        TicketLogService::logAssignment($ticket, $oldAssignedTo, $validated['assigned_to'], $oldAgent?->name, $newAgent?->name);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket assigned successfully');
    }

    /**
     * Close a ticket.
     */
    public function close(Ticket $ticket): RedirectResponse
    {
        $oldStatus = $ticket->status;
        $ticket->close();

        // Log status change
        TicketLogService::logStatusChange($ticket, $oldStatus, 'closed');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed successfully');
    }

    /**
     * Resolve a ticket.
     */
    public function resolve(Ticket $ticket): RedirectResponse
    {
        $oldStatus = $ticket->status;
        $ticket->resolve();

        // Log status change
        TicketLogService::logStatusChange($ticket, $oldStatus, 'resolved');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket resolved successfully');
    }

    /**
     * Delete a ticket (soft delete).
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully');
    }

    /**
     * Get messages for a ticket (for AJAX polling).
     */
    public function getMessages(Ticket $ticket): JsonResponse
    {
        $messages = $ticket->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'channel' => $message->channel,
                    'created_at' => $message->created_at->format('H:i'),
                    'created_at_full' => $message->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'messages' => $messages,
            'count' => $messages->count(),
        ]);
    }

    /**
     * Get count of new messages on assigned tickets for the current user.
     */
    public function getUnreadMessagesCount(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['count' => 0]);
        }

        // Count recent customer messages on tickets assigned to this user
        $count = Ticket::where('assigned_to', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->withCount([
                'messages' => function ($query) {
                    $query->where('message_type', 'customer')
                        ->where('created_at', '>=', now()->subHours(24));
                }
            ])
            ->get()
            ->sum('messages_count');

        return response()->json(['count' => $count]);
    }

}


