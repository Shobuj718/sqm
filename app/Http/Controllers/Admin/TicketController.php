<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;


class TicketController extends Controller
{
    /**
     * Display a listing of all tickets.
     */
    public function index(Request $request): View
    {
        $query = Ticket::with(['facebookPage', 'assignedAgent'])
            ->orderBy('created_at', 'desc');

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

        return view('admin.tickets.index', compact('tickets', 'agents'));
    }

    /**
     * Display the specified ticket with full conversation history.
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load(['messages', 'facebookPage', 'assignedAgent']);
        
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

        // Update ticket status/priority/assignment
        $updates = array_filter([
            'status' => $validated['status'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
        ], fn ($value) => $value !== null);

        if (!empty($updates)) {
            $ticket->update($updates);
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

        $ticket->update($validated);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket assigned successfully');
    }

    /**
     * Close a ticket.
     */
    public function close(Ticket $ticket): RedirectResponse
    {
        $ticket->close();

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed successfully');
    }

    /**
     * Resolve a ticket.
     */
    public function resolve(Ticket $ticket): RedirectResponse
    {
        $ticket->resolve();

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
}

