<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;


class TicketController extends Controller
{
    private string $apiVersion = '25.0';

    /**
     * Display a listing of all tickets.
     */
    public function index(Request $request): View
    {

        if (auth()->check() && auth()->user()->hasRole('agent')) {
            $query = Ticket::with(['facebookPage', 'assignedAgent', 'latestMessage'])
                ->withCount(['messages as unread_messages_count' => function ($query) {
                    $query->where('message_type', 'customer')->where('is_read', false);
                }])
                ->where('assigned_to', auth()->user()->id)
                ->where('status', '!=', 'closed')
                ->orderBy('created_at', 'desc');
        } else {
            $query = Ticket::with(['facebookPage', 'assignedAgent', 'latestMessage'])
                ->withCount(['messages as unread_messages_count' => function ($query) {
                    $query->where('message_type', 'customer')->where('is_read', false);
                }])
                ->where('status', '!=', 'closed')
                ->orderBy('created_at', 'desc');
        }

        $summaryTotal = (clone $query)->count();
        $summaryOpen = (clone $query)->where('status', 'open')->count();
        $summaryInProgress = (clone $query)->where('status', 'waiting')->count();
        $summaryResolved = (clone $query)->where('status', 'solved')->count();

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
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
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

        return view('admin.tickets.conversations', compact('tickets', 'agents', 'summaryTotal', 'summaryOpen', 'summaryInProgress', 'summaryResolved'));
    }

    /**
     * Display the specified ticket with full conversation history.
     */
    public function show(Ticket $ticket, Request $request): View|\Illuminate\Http\JsonResponse
    {
        $ticket->load([
            'messages',
            'facebookPage',
            'assignedAgent',
            'logs',
            'tags'
        ]);

        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['agent']);
        })->get();

        if ($request->ajax()) {

            return response()->json([
                'html' => view(
                    'admin.tickets.chat-area',
                    compact('ticket', 'agents')
                )->render(),
                'ticket' => [
                    'id' => $ticket->id,
                    'customer_name' => $ticket->customer_name,
                    'channel' => $ticket->channel,
                    'facebook_page_name' => optional($ticket->facebookPage)->page_name,
                    'facebook_post_id' => $ticket->facebook_post_id,
                    'post_link' => $ticket->facebook_post_id ? 'https://www.facebook.com/' . $ticket->facebook_post_id : null,
                    'summary' => $ticket->summary ?? $ticket->subject ?? $ticket->initial_message,
                    'created_at' => optional($ticket->created_at)->toIso8601String(),
                    'tags' => $ticket->tags->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    })->toArray(),
                    'available_tags' => Tag::orderBy('name')->get(['id', 'name'])->toArray(),
                ],
            ]);

        }

        return view(
            'admin.tickets.show',
            compact('ticket', 'agents')
        );
    }

    /**
     * Update ticket status or assignment.
     */
    public function update(Request $request, Ticket $ticket)
    {
        try {
            $validated = $request->validate([
                'status' => 'nullable|in:open,waiting,solved,closed',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'assigned_to' => 'nullable|exists:users,id',
                'summary' => 'nullable|string',
                'tags' => 'sometimes|array',
                'tags.*' => 'integer|exists:labels,id',
                'agent_message' => 'nullable|string',
                'attachments.*' => 'file|max:10240',
            ]);

            // Store old values for logging
            $oldStatus = $ticket->status;
            $oldPriority = $ticket->priority;
            $oldAssignedTo = $ticket->assigned_to;
            $oldAgent = $ticket->assignedAgent;

            // Update ticket
            $updates = [];

            if (array_key_exists('status', $validated)) {
                $updates['status'] = $validated['status'];
            }

            if (array_key_exists('priority', $validated)) {
                $updates['priority'] = $validated['priority'];
            }

            if (array_key_exists('assigned_to', $validated)) {
                $updates['assigned_to'] = $validated['assigned_to'];
            }

            if (array_key_exists('summary', $validated)) {
                $updates['summary'] = $validated['summary'];
            }

            if (!empty($updates)) {
                $ticket->update($updates);

                // Status log
                if (
                    isset($updates['status']) &&
                    $updates['status'] !== $oldStatus
                ) {
                    TicketLogService::logStatusChange(
                        $ticket,
                        $oldStatus,
                        $updates['status']
                    );
                }

                // Priority log
                if (
                    isset($updates['priority']) &&
                    $updates['priority'] !== $oldPriority
                ) {
                    TicketLogService::logPriorityChange(
                        $ticket,
                        $oldPriority,
                        $updates['priority']
                    );
                }

                // Assignment log
                if (array_key_exists('assigned_to', $updates)) {
                    $newAgent = User::find($updates['assigned_to']);
                    $oldAgentName = $oldAgent?->name;
                    $newAgentName = $newAgent?->name;

                    TicketLogService::logAssignment(
                        $ticket,
                        $oldAssignedTo,
                        $updates['assigned_to'],
                        $oldAgentName,
                        $newAgentName
                    );
                }

                // Refresh availability status for affected agents.
                $affectedAgentIds = collect();
                if ($oldAssignedTo) {
                    $affectedAgentIds->push($oldAssignedTo);
                }
                if ($ticket->assigned_to) {
                    $affectedAgentIds->push($ticket->assigned_to);
                }

                $affectedAgentIds->unique()->each(function ($agentId) {
                    User::find($agentId)?->refreshAvailabilityStatusBasedOnLoad();
                });
            }

            if (array_key_exists('tags', $validated)) {
                $ticket->tags()->sync($validated['tags'] ?? []);
            }

            // Agent message or attachments
            if ($request->filled('agent_message') || $request->hasFile('attachments')) {
                $ticket->loadMissing('facebookPage');
                $agentMessage = $validated['agent_message'] ?? '';
                $attachments = [];

                foreach ($request->file('attachments', []) as $attachmentFile) {
                    if (!$attachmentFile->isValid()) {
                        continue;
                    }

                    $path = $attachmentFile->store('ticket-attachments', 'public');
                    $url = Storage::url($path);
                    if (!str_starts_with($url, 'http')) {
                        $url = url($url);
                    }

                    $attachments[] = [
                        'type' => $this->detectAttachmentType($attachmentFile),
                        'payload' => [
                            'url' => $url,
                            'storage_path' => $path,
                            'name' => $attachmentFile->getClientOriginalName(),
                        ],
                    ];
                }

                $message = $ticket->addMessage(
                    facebookMessageId: uniqid('agent_'),
                    senderFacebookId: auth()->id(),
                    message: $agentMessage,
                    attachments: $attachments,
                    messageType: 'agent',
                    channel: 'messenger'
                );

                TicketLogService::logMessageAdded($ticket, 'agent');

                if (!array_key_exists('status', $validated)) {
                    $oldStatusForAgentReply = $ticket->status;
                    if ($ticket->status !== 'waiting') {
                        $ticket->update(['status' => 'waiting']);
                        TicketLogService::logStatusChange($ticket, $oldStatusForAgentReply, 'waiting');
                    }
                }

                // Send Facebook Message and attachments
                if (
                    $ticket->facebookPage?->page_token &&
                    $ticket->customer_facebook_id
                ) {
                    try {
                        // Send the text portion first if present.
                        if (!empty($agentMessage)) {
                            Http::post('https://graph.facebook.com/v25.0/me/messages', [
                                'access_token' => $ticket->facebookPage->page_token,
                                'recipient' => [
                                    'id' => $ticket->customer_facebook_id,
                                ],
                                'message' => [
                                    'text' => $agentMessage,
                                ],
                            ]);
                        }

                        // Send each attachment as a separate message payload.
                        foreach ($attachments as $index => $attachment) {
                            $fbPayload = [
                                'type' => $attachment['type'],
                                'payload' => [
                                    'is_reusable' => true,
                                ],
                            ];

                            $localPath = null;
                            if (isset($attachment['payload']['storage_path']) && Storage::disk('public')->exists($attachment['payload']['storage_path'])) {
                                $localPath = Storage::disk('public')->path($attachment['payload']['storage_path']);
                            }

                            if ($localPath && file_exists($localPath)) {
                                // First upload the attachment to get attachment ID
                                $uploadResponse = Http::attach(
                                    'filedata',
                                    fopen($localPath, 'r'),
                                    $attachment['payload']['name'] ?? basename($localPath)
                                )
                                ->post("https://graph.facebook.com/v25.0/me/message_attachments?access_token={$ticket->facebookPage->page_token}", [
                                    'message' => json_encode(['attachment' => $fbPayload]),
                                ]);

                                if ($uploadResponse->successful()) {
                                    $uploadData = $uploadResponse->json();
                                    Log::info('Facebook upload response', ['uploadData' => $uploadData]);

                                    if (isset($uploadData['attachment_id'])) {
                                        // Send message with attachment ID
                                        Http::post('https://graph.facebook.com/v25.0/me/messages', [
                                            'access_token' => $ticket->facebookPage->page_token,
                                            'recipient' => [
                                                'id' => $ticket->customer_facebook_id,
                                            ],
                                            'message' => [
                                                'attachment' => [
                                                    'type' => $attachment['type'],
                                                    'payload' => [
                                                        'attachment_id' => $uploadData['attachment_id'],
                                                    ],
                                                ],
                                            ],
                                        ]);

                                        // Try to get the attachment URL
                                        try {
                                            sleep(1); // Wait a bit for processing
                                            $attachmentResponse = Http::get("https://graph.facebook.com/v25.0/{$uploadData['attachment_id']}", [
                                                'access_token' => $ticket->facebookPage->page_token,
                                            ]);

                                            if ($attachmentResponse->successful()) {
                                                $attachmentData = $attachmentResponse->json();
                                                Log::info('Facebook attachment data', ['attachmentData' => $attachmentData]);

                                                if (isset($attachmentData['url'])) {
                                                    $attachments[$index]['payload']['url'] = $attachmentData['url'];
                                                    Log::info('Updated attachment with Facebook URL', ['url' => $attachmentData['url']]);
                                                } elseif (isset($attachmentData['image_data']['url'])) {
                                                    $attachments[$index]['payload']['url'] = $attachmentData['image_data']['url'];
                                                    Log::info('Updated attachment with Facebook URL', ['url' => $attachmentData['image_data']['url']]);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            Log::error('Exception getting Facebook attachment URL', [
                                                'attachment_id' => $uploadData['attachment_id'],
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    }
                                } else {
                                    Log::warning('Facebook upload failed', [
                                        'status' => $uploadResponse->status(),
                                        'body' => $uploadResponse->body()
                                    ]);
                                }
                            } else {
                                $fbPayload['payload']['url'] = $attachment['payload']['url'];

                                Http::post('https://graph.facebook.com/v25.0/me/messages', [
                                    'access_token' => $ticket->facebookPage->page_token,
                                    'recipient' => [
                                        'id' => $ticket->customer_facebook_id,
                                    ],
                                    'message' => [
                                        'attachment' => $fbPayload,
                                    ],
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        report($e);
                    }
                }
            }

            $lastMessage = $ticket->messages()
                ->reorder('id', 'desc')
                ->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket updated successfully',
                'ticket_id' => $ticket->id,
                'ticket_status' => $ticket->status,
                'priority' => $ticket->priority,
                'assigned_to' => $ticket->assigned_to,
                'chat_message' => [
                    'id' => $lastMessage?->id,
                    'message' => $lastMessage?->message,
                    'message_type' => $lastMessage?->message_type,
                    'created_at' => optional($lastMessage?->created_at)->toDateTimeString(),
                    'facebook_page_name' => $ticket->facebookPage?->page_name,
                    'customer_name' => $ticket->customer_name,
                    'attachments' => $lastMessage?->attachments ?? [],
                ]
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function detectAttachmentType($file): string
    {
        $mimeType = $file->getMimeType() ?? '';

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }

    /**
     * Assign ticket to an agent.
     */
    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldAssignedTo = $ticket->assigned_to;
        $oldAgent = $ticket->assignedAgent;
        $newAgent = $validated['assigned_to'] ? User::find($validated['assigned_to']) : null;

        $ticket->update($validated);

        // Log assignment or unassignment
        if ($oldAssignedTo !== $validated['assigned_to']) {
            TicketLogService::logAssignment(
                $ticket,
                $oldAssignedTo,
                $validated['assigned_to'],
                $oldAgent?->name,
                $newAgent?->name
            );
        }

        if ($oldAssignedTo) {
            User::find($oldAssignedTo)?->refreshAvailabilityStatusBasedOnLoad();
        }

        $newAgent?->refreshAvailabilityStatusBasedOnLoad();

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket assignment updated successfully');
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

        $ticket->assignedAgent?->refreshAvailabilityStatusBasedOnLoad();

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
        TicketLogService::logStatusChange($ticket, $oldStatus, 'solved');

        $ticket->assignedAgent?->refreshAvailabilityStatusBasedOnLoad();

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket marked as solved successfully');
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
                    'is_read' => $message->is_read,
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

        // Count unread customer messages on tickets assigned to this user
        $count = Ticket::where('assigned_to', $user->id)
            ->whereIn('status', ['open', 'waiting'])
            ->withCount([
                'messages' => function ($query) {
                    $query->where('message_type', 'customer')
                        ->where('is_read', false);
                }
            ])
            ->get()
            ->sum('messages_count');

        return response()->json(['count' => $count]);
    }

    /**
     * Return paginated ticket status overview for ticket assignment workflows.
     */
    public function statuses(Request $request)
    {
        $query = Ticket::with(['assignedAgent', 'facebookPage'])
            ->select(['id', 'customer_facebook_id', 'customer_name', 'subject', 'status', 'priority', 'assigned_to', 'facebook_page_id', 'channel', 'created_at', 'updated_at']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif ($request->assigned_to !== 'all') {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_facebook_id', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $tickets = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        $agentOptions = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'agent']);
        })->get(['id', 'name']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $tickets->items(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'agents' => $agentOptions,
            ]);
        }

        $summaryTotal = Ticket::count();
        $summaryOpen = Ticket::where('status', 'open')->count();
        $summaryInProgress = Ticket::where('status', 'waiting')->count();
        $summaryResolved = Ticket::where('status', 'solved')->count();

        return view('admin.tickets.index', compact('tickets', 'summaryTotal', 'summaryOpen', 'summaryInProgress', 'summaryResolved', 'agentOptions'));
    }

    /**
     * Bulk assign multiple tickets to an agent.
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'integer|exists:tickets,id',
            'assigned_to' => 'required|string',
        ]);

        $assignedTo = $validated['assigned_to'];
        if ($assignedTo === 'me') {
            $assignedTo = auth()->id();
        }

        if ($assignedTo !== null && !User::where('id', $assignedTo)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Agent not found'], 404);
            }

            return redirect()->back()->with('error', 'Agent not found');
        }

        $tickets = Ticket::whereIn('id', $validated['ticket_ids'])->get();
        $updatedCount = 0;
        $affectedAgentIds = collect();

        foreach ($tickets as $ticket) {
            $oldAssignedTo = $ticket->assigned_to;
            if ($oldAssignedTo !== $assignedTo) {
                $ticket->update(['assigned_to' => $assignedTo]);

                TicketLogService::logAssignment(
                    $ticket,
                    $oldAssignedTo,
                    $assignedTo,
                    User::find($oldAssignedTo)?->name,
                    User::find($assignedTo)?->name
                );

                if ($oldAssignedTo) {
                    $affectedAgentIds->push($oldAssignedTo);
                }
                if ($assignedTo) {
                    $affectedAgentIds->push($assignedTo);
                }

                $updatedCount++;
            }
        }

        $affectedAgentIds->unique()->each(function ($agentId) {
            User::find($agentId)?->refreshAvailabilityStatusBasedOnLoad();
        });

        $message = "{$updatedCount} tickets updated.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'assigned_to' => $assignedTo,
                'ticket_ids' => $tickets->pluck('id'),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Mark all messages on a ticket as read.
     */
    public function markMessagesAsRead(Ticket $ticket): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Only allow marking as read if user is assigned to this ticket
        if ($ticket->assigned_to !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $updated = $ticket->messages()
            ->where('message_type', 'customer')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} messages marked as read"
        ]);
    }

    /**
     * Mark all customer messages on a ticket as unread.
     */
    public function markMessagesAsUnread(Ticket $ticket): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($ticket->assigned_to !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $lastMessage = $ticket->messages()
            ->where('message_type', 'customer')
            ->latest('id')
            ->first();

        if ($lastMessage) {
            $lastMessage->is_read = false;
            $lastMessage->read_at = null;
            $lastMessage->save();
        }

        $unreadCount = $ticket->messages()
            ->where('message_type', 'customer')
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => "messages marked as unread",
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single message as read.
     */
    public function markMessageAsRead(\App\Models\SupportMessage $message): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $ticket = $message->ticket;

        // Only allow marking as read if user is assigned to this ticket
        if ($ticket->assigned_to !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message->markAsRead();

        return response()->json(['success' => true, 'message' => 'Message marked as read']);
    }
}


