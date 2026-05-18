<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNote;
use App\Models\FacebookPage;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Services\RagService;
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
                ->withMax('messages', 'created_at')
                ->where('assigned_to', auth()->user()->id)
                ->where('status', '!=', 'closed')
                ->orderByDesc('messages_max_created_at');
        } else {
            $query = Ticket::with(['facebookPage', 'assignedAgent', 'latestMessage'])
                ->withCount(['messages as unread_messages_count' => function ($query) {
                    $query->where('message_type', 'customer')->where('is_read', false);
                }])
                ->withMax('messages', 'created_at')
                ->where('status', '!=', 'closed')
                ->orderByDesc('messages_max_created_at');
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
            'messages.user',
            'facebookPage',
            'assignedAgent',
            'logs',
            'tags'
        ]);

        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['agent']);
        })->get();

        if ($request->ajax()) {

            // Get current agent's note for this view
            $agentNote = auth()->user()->notes()->first();

            return response()->json([
                'html' => view(
                    'admin.tickets.chat-area',
                    compact('ticket', 'agents')
                )->render(),
                'ticket' => [
                    'id' => $ticket->id,
                    'customer_name' => $ticket->customer_name,
                    'customer_facebook_id' => $ticket->customer_facebook_id,
                    'channel' => $ticket->channel,
                    'facebook_page_id' => optional($ticket->facebookPage)->page_id,
                    'facebook_page_name' => optional($ticket->facebookPage)->page_name,
                    'facebook_post_id' => $ticket->facebook_post_id,
                    'post_link' => $ticket->facebook_post_id ? 'https://www.facebook.com/' . $ticket->facebook_post_id : null,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'assigned_to' => $ticket->assigned_to,
                    'summary' => $ticket->summary ?? $ticket->subject ?? $ticket->initial_message,
                    'created_at' => optional($ticket->created_at)->toIso8601String(),
                    'agent_name' => optional($ticket->assignedAgent)->name,
                    'agents' => $agents->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray(),
                    'tags' => $ticket->tags->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                            'category' => $tag->category ?? null,
                        ];
                    })->toArray(),
                    'available_tags' => Tag::orderBy('name')->get(['id', 'name', 'category'])->toArray(),
                    'agent_note' => $agentNote ? $agentNote->content : '',
                    'agent_note_id' => $agentNote ? $agentNote->id : null,
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
                'business_tag' => 'nullable|integer|exists:labels,id',
                'sentiment_tag' => 'nullable|integer|exists:labels,id',
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

            // Handle new category-based tags if provided
            if (array_key_exists('business_tag', $validated) || array_key_exists('sentiment_tag', $validated)) {
                // Load current tags
                $ticket->load('tags');

                // Keep tags that are not business/sentiment
                $tagsToKeep = $ticket->tags->filter(function ($t) {
                    $cat = strtolower((string)($t->category ?? ''));
                    return !in_array($cat, ['business', 'sentiment']);
                })->pluck('id')->toArray();

                // Validate and add business tag if provided
                $businessId = $validated['business_tag'] ?? null;
                if ($businessId) {
                    $businessTag = Tag::find($businessId);
                    if (!$businessTag || strtolower((string)($businessTag->category ?? '')) !== 'business') {
                        return response()->json(['status' => 'error', 'message' => 'Invalid business tag selected'], 422);
                    }
                    $tagsToKeep[] = $businessTag->id;
                }

                // Validate and add sentiment tag if provided
                $sentimentId = $validated['sentiment_tag'] ?? null;
                if ($sentimentId) {
                    $sentimentTag = Tag::find($sentimentId);
                    if (!$sentimentTag || strtolower((string)($sentimentTag->category ?? '')) !== 'sentiment') {
                        return response()->json(['status' => 'error', 'message' => 'Invalid sentiment tag selected'], 422);
                    }
                    $tagsToKeep[] = $sentimentTag->id;
                }

                // Ensure unique IDs
                $tagsToKeep = array_values(array_unique($tagsToKeep));

                $ticket->tags()->sync($tagsToKeep);
            } elseif (array_key_exists('tags', $validated)) {
                // Legacy behavior: sync flat tags array
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
                    channel: 'messenger',
                    userId: auth()->id()
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
                ->with('user')
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
                    'sender_type' => $lastMessage?->message_type,
                    'user_id' => $lastMessage?->user_id,
                    'created_at' => optional($lastMessage?->created_at)->toDateTimeString(),
                    'facebook_page_name' => $ticket->facebookPage?->page_name,
                    'customer_name' => $ticket->customer_name,
                    'agent_name' => $lastMessage?->user?->name ?? auth()->user()?->name ?? $ticket->assignedAgent?->name,
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

    /**
     * Get AI suggestions for ticket reply.
     */
    public function getAISuggestions(Ticket $ticket): JsonResponse
    {
        try {
            $ragService = app(RagService::class);

            // Build conversation history
            $messages = $ticket->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            $conversation = '';
            foreach ($messages as $message) {
                $sender = $message->message_type === 'customer' ? 'Customer' :
                         ($message->user ? $message->user->name : 'Agent');
                $conversation .= "{$sender}: {$message->message}\n";
            }

            if (trim($conversation) === '') {
                $conversation = trim("Customer: " . ($ticket->initial_message ?? $ticket->subject ?? ''));
            }

            // Detect conversation language
            $language = $this->detectConversationLanguage($conversation);

            $ragSearchText = $this->buildRagSuggestionSearchText($ticket, $conversation);
            $facebookPageId = $this->resolveRagFacebookPageId($ticket);
            $matches = $ragService->search(
                $ragSearchText,
                $facebookPageId,
                5,
                (float) config('rag.suggestion_min_score', 0.2)
            );

            if ($matches->isEmpty()) {
                $matches = $ragService->search(
                    $ragSearchText,
                    $facebookPageId,
                    5,
                    (float) config('rag.suggestion_fallback_min_score', 0.05)
                );
            }

            if ($matches->isEmpty() && $facebookPageId) {
                $matches = $ragService->search(
                    $ragSearchText,
                    null,
                    5,
                    (float) config('rag.suggestion_fallback_min_score', 0.05)
                );
            }

            if ($matches->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching RAG knowledge found. Please upload company knowledge or lower RAG_SUGGESTION_FALLBACK_MIN_SCORE.'
                ], 404);
            }

            $prompt = $this->buildRagSuggestionsPrompt($conversation, $matches, $language);
            $suggestions = $this->generateOpenAIRagSuggestions($prompt);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
                'rag_matches' => $matches->map(fn (array $match): array => [
                    'document' => $match['document']->title,
                    'score' => round($match['score'], 4),
                ])->all(),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Suggestions Error', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build a focused RAG query from the latest ticket context.
     */
    private function buildRagSuggestionSearchText(Ticket $ticket, string $conversation): string
    {
        $latestCustomerMessage = $ticket->messages()
            ->where('message_type', 'customer')
            ->latest('created_at')
            ->value('message');

        $latestCustomerMessage ??= $ticket->initial_message ?? $ticket->subject;
        $expandedIntent = $this->expandShortRagQuery((string) $latestCustomerMessage);

        return trim(implode("\n\n", array_filter([
            $latestCustomerMessage,
            $expandedIntent,
            $ticket->summary,
            $ticket->subject,
            $ticket->initial_message,
            \Illuminate\Support\Str::limit($conversation, 1500),
        ])));
    }

    /**
     * Add intent words for very short Facebook comments so embedding search has enough signal.
     */
    private function expandShortRagQuery(string $message): ?string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $message) ?? $message));

        if ($normalized === '') {
            return null;
        }

        $plain = preg_replace('/[^\p{L}\p{N}\s?]/u', '', $normalized) ?? $normalized;
        $wordCount = str_word_count($plain);

        if ($wordCount > 5 && mb_strlen($plain) > 40) {
            return null;
        }

        $positive = ['nice', 'good', 'good job', 'great', 'awesome', 'beautiful', 'love it', 'excellent', 'wow', 'best'];
        $interest = ['price', 'details', 'inbox', 'available', 'how much', 'need this', 'want to buy', 'buy', 'pp'];
        $negative = ['bad', 'not good', 'poor service', 'disappointed', 'worst'];

        foreach ($positive as $phrase) {
            if (str_contains($plain, $phrase)) {
                return 'short positive comments nice good job great awesome beautiful love it excellent wow suggested replies';
            }
        }

        foreach ($interest as $phrase) {
            if (str_contains($plain, $phrase)) {
                return 'interest comments price details inbox available how much need this want to buy suggested replies';
            }
        }

        foreach ($negative as $phrase) {
            if (str_contains($plain, $phrase)) {
                return 'negative short comments bad poor service disappointed worst complaint suggested replies';
            }
        }

        if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $message)) {
            return 'generic emoji comments heart clap smile fire thumbs up suggested replies';
        }

        return 'short Facebook page comment suggested reply';
    }

    /**
     * Resolve the Facebook page database ID used by RAG documents.
     */
    private function resolveRagFacebookPageId(Ticket $ticket): ?int
    {
        $ticket->loadMissing('facebookPage');

        if ($ticket->facebookPage?->id) {
            return $ticket->facebookPage->id;
        }

        if (!$ticket->facebook_page_id) {
            return null;
        }

        $facebookPage = FacebookPage::query()
            ->where('id', $ticket->facebook_page_id)
            ->orWhere('page_id', $ticket->facebook_page_id)
            ->first();

        return $facebookPage?->id;
    }

    /**
     * Build an AI prompt that is grounded in retrieved RAG chunks.
     */
    private function buildRagSuggestionsPrompt(string $conversation, \Illuminate\Support\Collection $matches, string $language): string
    {
        $knowledge = $matches
            ->map(function (array $match, int $index): string {
                $document = $match['document'];
                $source = $document->title;
                $page = $document->facebookPage?->page_name ?? 'Global';
                $rank = $index + 1;

                return "Source {$rank}: {$source} ({$page})\n{$match['content']}";
            })
            ->implode("\n\n---\n\n");

        return "You are a company customer support assistant. Use only the company knowledge below to suggest replies. Do not invent policy, price, stock, delivery date, refund approval, or discount details. If the knowledge is not enough, ask for the missing customer detail or say the team will check.\n\nReply language: {$language}\n\nCompany knowledge:\n{$knowledge}\n\nConversation:\n{$conversation}\n\nReturn exactly 2 short professional reply suggestions for the next agent message. Each suggestion must be one sentence or less. Do not include labels, numbering, quotes, or explanations.";
    }

    /**
     * Generate suggestions from the RAG-grounded prompt.
     */
    private function generateOpenAIRagSuggestions(string $prompt): array
    {
        if (!env('OPENAI_API_KEY')) {
            throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $response = app('openai')->chat()->create([
            'model' => config('rag.chat_model', 'gpt-5.4-mini'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_completion_tokens' => 220,
            'temperature' => 0.2,
        ]);

        $suggestions = $this->parseStrictAISuggestions($response->choices[0]->message->content ?? '');

        if (empty($suggestions)) {
            throw new \Exception('OpenAI did not return a usable RAG suggestion.');
        }

        return $suggestions;
    }

    /**
     * Parse RAG suggestions without generic fallback replies.
     */
    private function parseStrictAISuggestions(string $content): array
    {
        $suggestions = [];
        $lines = explode("\n", trim($content));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\d+[\).\s-]+(.+)$/', $line, $matches) ||
                preg_match('/^[-*•]\s*(.+)$/u', $line, $matches)) {
                $line = trim($matches[1]);
            }

            $line = trim($line, " \t\n\r\0\x0B\"'");

            if ($line !== '' && !preg_match('/^(suggestion|reply|response)\s*\d*[:.-]/i', $line)) {
                $suggestions[] = $line;
            }
        }

        if (empty($suggestions)) {
            foreach (preg_split('/\n\s*\n/', $content) ?: [] as $part) {
                $part = trim($part, " \t\n\r\0\x0B\"'");

                if ($part !== '') {
                    $suggestions[] = $part;
                }
            }
        }

        return array_slice(array_values(array_unique($suggestions)), 0, 3);
    }

    /**
     * Generate AI summary for ticket conversation.
     */
    public function generateSummary(Ticket $ticket): JsonResponse
    {
        try {
            // Load all messages
            $messages = $ticket->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No messages to summarize'
                ], 400);
            }

            // Build conversation text
            $conversationText = '';
            foreach ($messages as $message) {
                $sender = $message->message_type === 'customer' ? 'Customer' :
                         ($message->user ? $message->user->name : 'Agent');
                $conversationText .= "{$sender}: {$message->message}\n";
            }

            // Detect conversation language
            $language = $this->detectConversationLanguage($conversationText);

            // Create prompt for AI in the conversation language
            $prompt = $this->buildSummaryPrompt($conversationText, $language);

            // Generate summary using AI
            $summary = $this->generateAISummaryText($prompt);

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('AI Summary Error', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detect the language of the conversation.
     */
    private function detectConversationLanguage(string $text): string
    {
        // Language detection based on character patterns
        // Bengali: \x{0980}-\x{09FF}
        // Arabic: \x{0600}-\x{06FF}
        // Hebrew: \x{0590}-\x{05FF}
        // Russian/Cyrillic: \x{0400}-\x{04FF}
        // Chinese: \x{4E00}-\x{9FFF}
        // Japanese: \x{3040}-\x{309F} (Hiragana), \x{30A0}-\x{30FF} (Katakana)
        // Korean: \x{AC00}-\x{D7AF}

        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return 'Bengali';
        }
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'Arabic';
        }
        if (preg_match('/[\x{0590}-\x{05FF}]/u', $text)) {
            return 'Hebrew';
        }
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'Russian';
        }
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'Chinese';
        }
        if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text)) {
            return 'Japanese';
        }
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'Korean';
        }
        if (preg_match('/[éèêëàâäùûüôöçñÉÈÊËÀÂÄÙÛÜÔÖÇÑ]/u', $text)) {
            return 'French';
        }
        if (preg_match('/[äöüßÄÖÜ]/u', $text)) {
            return 'German';
        }
        if (preg_match('/[áéíóúñ¿¡ÁÉÍÓÚÑ]/u', $text)) {
            return 'Spanish';
        }
        if (preg_match('/[àèìòùÀÈÌÒÙ]/u', $text)) {
            return 'Italian';
        }
        if (preg_match('/[ãõçÃÕÇ]/u', $text)) {
            return 'Portuguese';
        }

        return 'English';
    }

    /**
     * Build a language-specific prompt for suggestions.
     */
    private function buildSuggestionsPrompt(string $conversation, string $language): string
    {
        $prompts = [
            'Bengali' => "আপনি একজন গ্রাহক সহায়তা এজেন্ট। নিম্নলিখিত কথোপকথন বিশ্লেষণ করুন এবং কেবল পরবর্তী এজেন্ট উত্তরের জন্য 2টি সংক্ষিপ্ত, পেশাদার পরামর্শ দিন। প্রতিটি পরামর্শ এক বা দুই বাক্যের বেশি নয় এবং গ্রাহকের উদ্বেগ সরাসরি সম্বোধন করুন। কোন কাস্টমার টেক্সট বা কথোপকথন লেবেল যোগ করবেন না।\n\nকথোপকথন:\n{$conversation}\n\nপরবর্তী এজেন্ট উত্তরের জন্য 2টি সংক্ষিপ্ত পরামর্শ দিন:",
            'Arabic' => "أنت وكيل دعم العملاء. قم بتحليل المحادثة التالية وقدم فقط اقتراحين قصيرين ومهنيين للرد التالي من الوكيل. يجب ألا تزيد كل اقتراح عن جملة أو جملتين وأن تعالج مخاوف العميل مباشرة. لا تدرج نص العميل أو تسميات المحادثة.\n\nالمحادثة:\n{$conversation}\n\nقدم اقتراحين قصيرين لرد الوكيل التالي:",
            'Hebrew' => "אתה סוכן תמיכה של הלקוח. בהתאם לשיחה הבאה, ספק 2-3 הצעות תגובה מועילות ומקצועיות. כל הצעה צריכה להיות תמציתית ולהתייחס ישירות לדאגות הלקוח.\n\nשיחה:\n{$conversation}\n\nספק 2-3 הצעות תגובה:",
            'Russian' => "Вы являетесь агентом по поддержке клиентов. На основе следующего разговора предоставьте 2-3 полезных и профессиональных предложения ответов. Каждое предложение должно быть кратким и напрямую решать проблемы клиента.\n\nРазговор:\n{$conversation}\n\nПредложите 2-3 варианта ответов:",
            'Chinese' => "你是客户支持代理。根据以下对话，提供2-3条有用且专业的回复建议。每条建议应简明扼要，直接解决客户的疑虑。\n\n对话：\n{$conversation}\n\n提供2-3条回复建议：",
            'Japanese' => "あなたはカスタマーサポートエージェントです。以下の会話に基づいて、2〜3つの有用で専門的な返信提案を提供してください。各提案は簡潔で、顧客の懸念に直接対応する必要があります。\n\n会話：\n{$conversation}\n\n返信の提案を2〜3つ提供してください:",
            'Korean' => "고객 지원 상담원입니다. 다음 대화를 바탕으로 2-3개의 유용하고 전문적인 회신 제안을 제공하십시오. 각 제안은 간결해야 하며 고객의 우려사항을 직접 해결해야 합니다.\n\n대화:\n{$conversation}\n\n2-3개의 회신 제안을 제공하십시오:",
            'French' => "Vous êtes un agent du service clientèle. En fonction de la conversation suivante, fournissez 2-3 suggestions de réponse utiles et professionnelles. Chaque suggestion doit être concise et aborder directement les préoccupations du client.\n\nConversation:\n{$conversation}\n\nFournissez 2-3 suggestions de réponse:",
            'German' => "Sie sind ein Kundensupportmitarbeiter. Basierend auf dem folgenden Gespräch geben Sie 2-3 hilfreiche und professionelle Antwortvorschläge ab. Jeder Vorschlag sollte prägnant sein und die Bedenken des Kunden direkt ansprechen.\n\nGespräch:\n{$conversation}\n\nMachen Sie 2-3 Antwortvorschläge:",
            'Spanish' => "Eres un agente de atención al cliente. Basado en la siguiente conversación, proporciona 2-3 sugerencias de respuesta útiles y profesionales. Cada sugerencia debe ser concisa y abordar directamente las preocupaciones del cliente.\n\nConversación:\n{$conversation}\n\nProporciona 2-3 sugerencias de respuesta:",
            'Italian' => "Sei un agente di supporto clienti. Sulla base della seguente conversazione, fornisci 2-3 suggerimenti di risposta utili e professionali. Ogni suggerimento deve essere conciso e affrontare direttamente le preoccupazioni del cliente.\n\nConversazione:\n{$conversation}\n\nFornisci 2-3 suggerimenti di risposta:",
            'Portuguese' => "Você é um agente de suporte ao cliente. Com base na conversa a seguir, forneça 2-3 sugestões de resposta úteis e profissionais. Cada sugestão deve ser concisa e abordar diretamente as preocupações do cliente.\n\nConversa:\n{$conversation}\n\nFornça 2-3 sugestões de resposta:",
            'English' => "You are a customer support agent. Analyze the previous conversation and suggest 2 very short, professional replies for the next agent message. Each reply should be one sentence or less and directly address the customer's concern. Do not repeat the customer text or conversation labels.\n\nConversation:\n{$conversation}\n\nProvide 2 short suggested agent replies for the next reply:",
        ];

        return $prompts[$language] ?? $prompts['English'];
    }

    /**
     * Build a language-specific prompt for summary.
     */
    private function buildSummaryPrompt(string $conversation, string $language): string
    {
        $prompts = [
            'Bengali' => "নিম্নলিখিত গ্রাহক সহায়তা কথোপকথন বিশ্লেষণ করুন এবং একটি সংক্ষিপ্ত, পেশাদার সারসংক্ষেপ প্রদান করুন (সর্বাধিক 2-3 বাক্য)। মূল সমস্যা, গ্রাহকের উদ্বেগ এবং সমাধানের অবস্থার উপর ফোকাস করুন।\n\nকথোপকথন:\n{$conversation}\n\nসারসংক্ষেপ:",
            'Arabic' => "حلل محادثة دعم العملاء التالية وقدم ملخصاً موجزاً واحترافياً (بحد أقصى 2-3 جمل). ركز على المشاكل الرئيسية ومخاوف العميل وحالة الحل.\n\nالمحادثة:\n{$conversation}\n\nالملخص:",
            'Hebrew' => "נתח את שיחת התמיכה של הלקוח הבאה וספק סיכום תמציתי והמקצועי (מקסימום 2-3 משפטים). התמקד בנושאים מרכזיים, דאגות הלקוח ומצב הפתרון.\n\nשיחה:\n{$conversation}\n\nסיכום:",
            'Russian' => "Проанализируйте следующий диалог поддержки клиентов и предоставьте краткое профессиональное резюме (максимум 2-3 предложения). Сосредоточьтесь на ключевых проблемах, обеспокоенности клиента и статусе разрешения.\n\nРазговор:\n{$conversation}\n\nРезюме:",
            'Chinese' => "分析以下客户支持对话，并提供简洁专业的摘要（最多2-3句话）。重点关注关键问题、客户的疑虑和解决状态。\n\n对话：\n{$conversation}\n\n摘要：",
            'Japanese' => "以下のカスタマーサポート会話を分析し、簡潔で専門的な要約を提供してください（最大2-3文）。重要な問題、顧客の懸念、および解決状況に焦点を当ててください。\n\n会話：\n{$conversation}\n\n要約：",
            'Korean' => "다음 고객 지원 대화를 분석하고 간결한 전문적 요약을 제공하십시오 (최대 2-3개 문장). 주요 문제, 고객의 우려사항 및 해결 상태에 중점을 두십시오.\n\n대화:\n{$conversation}\n\n요약:",
            'French' => "Analysez la conversation de support client suivante et fournissez un résumé concis et professionnel (maximum 2-3 phrases). Concentrez-vous sur les problèmes clés, les préoccupations des clients et l'état de la résolution.\n\nConversation:\n{$conversation}\n\nRésumé:",
            'German' => "Analysieren Sie das folgende Kundensupportgespräch und geben Sie eine prägnante, professionelle Zusammenfassung (maximal 2-3 Sätze) ab. Konzentrieren Sie sich auf Schlüsselprobleme, Kundenbedenken und Lösungsstatus.\n\nGespräch:\n{$conversation}\n\nZusammenfassung:",
            'Spanish' => "Analiza la siguiente conversación de soporte al cliente y proporciona un resumen conciso y profesional (máximo 2-3 oraciones). Enfócate en los problemas clave, las preocupaciones del cliente y el estado de la resolución.\n\nConversación:\n{$conversation}\n\nResumen:",
            'Italian' => "Analizza la seguente conversazione di supporto ai clienti e fornisci un riassunto conciso e professionale (massimo 2-3 frasi). Concentrati sui problemi chiave, sulle preoccupazioni dei clienti e sullo stato della risoluzione.\n\nConversazione:\n{$conversation}\n\nRiassunto:",
            'Portuguese' => "Analise a conversa de suporte ao cliente a seguir e forneça um resumo conciso e profissional (máximo 2-3 frases). Concentre-se nos problemas-chave, nas preocupações do cliente e no status da resolução.\n\nConversa:\n{$conversation}\n\nResumo:",
            'English' => "Analyze the following customer support conversation and provide a concise, professional summary (2-3 sentences maximum). Focus on the key issues, customer concerns, and resolution status.\n\nConversation:\n{$conversation}\n\nSummary:",
        ];

        return $prompts[$language] ?? $prompts['English'];
    }

    /**
     * Resolve the AI provider to use.
     */
    private function resolveAIProvider(): string
    {
        $provider = strtolower(trim(env('AI_PROVIDER', 'openai')));
        $hasOpenAIKey = !empty(env('OPENAI_API_KEY'));
        $hasHuggingFaceToken = !empty(env('HUGGING_FACE_TOKEN'));

        if ($provider === 'huggingface') {
            if ($hasHuggingFaceToken) {
                return 'huggingface';
            }
            if ($hasOpenAIKey) {
                return 'openai';
            }
            throw new \Exception('No AI provider is properly configured. Set HUGGING_FACE_TOKEN or OPENAI_API_KEY.');
        }

        if ($provider === 'openai') {
            if ($hasOpenAIKey) {
                return 'openai';
            }
            if ($hasHuggingFaceToken) {
                return 'huggingface';
            }
            throw new \Exception('No AI provider is properly configured. Set OPENAI_API_KEY or HUGGING_FACE_TOKEN.');
        }

        if ($provider === 'auto') {
            if ($hasOpenAIKey) {
                return 'openai';
            }
            if ($hasHuggingFaceToken) {
                return 'huggingface';
            }
            throw new \Exception('No AI provider is properly configured. Set OPENAI_API_KEY or HUGGING_FACE_TOKEN.');
        }

        throw new \Exception('Unsupported AI_PROVIDER value: ' . $provider);
    }

    /**
     * Generate summary text using OpenAI or Hugging Face.
     */
    private function generateAISummaryText(string $prompt): string
    {
        $provider = $this->resolveAIProvider();

        if ($provider === 'huggingface') {
            return $this->generateHuggingFaceSummary($prompt);
        }

        return $this->generateOpenAISummary($prompt);
    }

    /**
     * Generate summary using OpenAI.
     */
    private function generateOpenAISummary(string $prompt): string
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OpenAI API key not configured.');
        }

        $client = app('openai');

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 200,
            'temperature' => 0.5,
        ]);

        return trim($response->choices[0]->message->content ?? '');
    }

    /**
     * Generate summary using Hugging Face.
     */
    private function generateHuggingFaceSummary(string $prompt): string
    {
        $apiKey = env('HUGGING_FACE_TOKEN');

        if (!$apiKey) {
            throw new \Exception('Hugging Face token not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api-inference.huggingface.co/models/gpt2', [
            'inputs' => $prompt,
            'parameters' => [
                'max_length' => 150,
                'temperature' => 0.5,
                'do_sample' => true,
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Hugging Face API request failed: ' . $response->body());
        }

        $body = $response->json();

        if (!isset($body[0]['generated_text'])) {
            throw new \Exception('Invalid response from Hugging Face API');
        }

        $content = $body[0]['generated_text'] ?? '';

        // Remove the original prompt from the response
        $content = str_replace($prompt, '', $content);

        return trim($content);
    }

    /**
     * Generate AI suggestions using OpenAI or Hugging Face.
     */
    private function generateAISuggestionsList(string $prompt): array
    {
        $provider = $this->resolveAIProvider();

        if ($provider === 'huggingface') {
            return $this->generateHuggingFaceSuggestions($prompt);
        }

        return $this->generateOpenAISuggestions($prompt);
    }

    /**
     * Generate AI suggestions using OpenAI.
     */
    private function generateOpenAISuggestions(string $prompt): array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $client = app('openai');

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7,
        ]);

        $content = $response->choices[0]->message->content ?? '';

        return $this->parseAISuggestions($content);
    }

    /**
     * Generate AI suggestions using Hugging Face.
     */
    private function generateHuggingFaceSuggestions(string $prompt): array
    {
        $apiKey = env('HUGGING_FACE_TOKEN');

        if (!$apiKey) {
            throw new \Exception('Hugging Face token not configured. Please set HUGGING_FACE_TOKEN in your .env file.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api-inference.huggingface.co/models/gpt2', [
            'inputs' => $prompt,
            'parameters' => [
                'max_length' => 200,
                'temperature' => 0.7,
                'do_sample' => true,
                'num_return_sequences' => 3,
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Hugging Face API request failed: ' . $response->body());
        }

        $body = $response->json();

        if (!isset($body[0]['generated_text'])) {
            throw new \Exception('Invalid response from Hugging Face API');
        }

        $content = $body[0]['generated_text'] ?? '';

        // Remove the original prompt from the response
        $content = str_replace($prompt, '', $content);
        $content = trim($content);

        return $this->parseAISuggestions($content);
    }

    /**
     * Parse AI response into suggestions array.
     */
    private function parseAISuggestions(string $content): array
    {
        $suggestions = [];
        $lines = explode("\n", trim($content));

        foreach ($lines as $line) {
            $line = trim($line);
            // Look for numbered suggestions or bullet points
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches) ||
                preg_match('/^[-•*]\s*(.+)$/', $line, $matches)) {
                $suggestions[] = trim($matches[1]);
            } elseif (!empty($line) && !preg_match('/^(suggestion|reply|response)/i', $line)) {
                // If it's a plain line that looks like a suggestion
                if (strlen($line) > 10 && count($suggestions) < 3) {
                    $suggestions[] = $line;
                }
            }
        }

        // If parsing failed, split by double newlines or just return the whole content as one suggestion
        if (empty($suggestions)) {
            $parts = preg_split('/\n\s*\n/', $content);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part) && strlen($part) > 10) {
                    $suggestions[] = $part;
                }
            }
        }

        // Ensure we have at least 2 suggestions, max 3
        $suggestions = array_slice($suggestions, 0, 3);

        if (count($suggestions) < 2) {
            $suggestions = array_merge($suggestions, [
                "Thank you for your message. I'm here to help resolve this issue.",
                "I understand your concern. Let me assist you with this."
            ]);
            $suggestions = array_slice($suggestions, 0, 3);
        }

        return $suggestions;
    }

    /**
     * Save or update agent note
     */
    public function saveAgentNote(Request $request): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:5000',
            'note_id' => 'nullable|integer|exists:agent_notes,id'
        ]);

        $user = auth()->user();

        if ($request->filled('note_id')) {
            $note = $user->notes()->where('id', $request->note_id)->first();
            if ($note) {
                $note->update(['content' => $request->note]);
            } else {
                return response()->json(['success' => false, 'message' => 'Note not found'], 404);
            }
        } else {
            $note = $user->notes()->create(['content' => $request->note]);
        }

        return response()->json([
            'success' => true,
            'note_id' => $note->id,
            'message' => 'Note saved successfully',
        ]);
    }

    /**
     * List agent notes for current user
     */
    public function listAgentNotes(Request $request): JsonResponse
    {
        $user = auth()->user();
        $notes = $user->notes()->orderByDesc('created_at')->get(['id', 'content', 'created_at']);

        return response()->json(['success' => true, 'notes' => $notes]);
    }

    /**
     * Delete an agent note
     */
    public function deleteAgentNote(Request $request, $id): JsonResponse
    {
        $user = auth()->user();
        $note = $user->notes()->where('id', $id)->first();
        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note not found'], 404);
        }
        $note->delete();
        return response()->json(['success' => true, 'message' => 'Note deleted']);
    }
}
