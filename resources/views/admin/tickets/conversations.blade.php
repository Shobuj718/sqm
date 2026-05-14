<x-layouts.app>

    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 20px;
        }

        ::-webkit-scrollbar-thumb:window-inactive {
            background: #d1d5db;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        .dark ::-webkit-scrollbar-thumb:window-inactive {
            background: #4b5563;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .conversation-item {
            transition: background-color 0.2s ease;
        }

        .conversation-item:hover {
            background-color: #f0f2f5;
            border-color: #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .dark .conversation-item:hover {
            background-color: #374151;
            border-color: #4b5563;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .conversation-item:hover .conversation-menu-btn {
            opacity: 1 !important;
        }

        .conversation-item.selected {
            background-color: #e3f2fd;
            border-color: #93c5fd;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.12);
        }

        .dark .conversation-item.selected {
            background-color: #1e3a8a;
            border-color: #3b82f6;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }

        .sidebar-filter-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.45rem 0.9rem;
        }

        .dark .sidebar-filter-chip {
            border: 1px solid #4b5563;
            background-color: #374151;
            color: #d1d5db;
        }

        .unread-indicator {
            width: 8px;
            height: 8px;
            background-color: #1877f2;
            border-radius: 50%;
            position: absolute;
            top: 0;
            right: 0;
            border: 2px solid white;
        }

        .dark .unread-indicator {
            border-color: #1f2937;
        }

        #ticket-notification {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #ticket-notification.hidden {
            opacity: 0;
            transform: translateY(16px);
        }
    </style>

    <div class="h-[calc(100vh-64px)] flex bg-gray-50 dark:bg-gray-900 overflow-hidden">

        {{-- LEFT SIDEBAR --}}
        <div class="w-[320px] bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 flex flex-col shrink-0">

            {{-- HEADER --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-20">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Inbox
                    </h2>
                    <button type="button" class="inline-flex items-center justify-center h-9 w-9 rounded-full border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white transition-colors" title="Refresh inbox">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.93 4.93a10 10 0 0114.14 0 10 10 0 010 14.14M12 2v4m0 12v4m10-10h-4M6 12H2" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- CONVERSATION LIST --}}
            <div class="flex-1 overflow-y-auto py-2">

                @forelse($tickets as $ticket)

                    @php
                        $lastMessage = $ticket->latestMessage ?? $ticket->messages()->latest()->first();
                        $unreadCount = $ticket->unread_messages_count ?? 0;
                        $isUnread = $unreadCount > 0;
                        $isSelected = false; // We'll handle this with JS
                        $imagePreview = null;
                        if ($lastMessage?->attachments) {
                            foreach ($lastMessage->attachments as $attachment) {
                                if (($attachment['type'] ?? '') === 'image') {
                                    $imagePreview = $attachment['payload']['url'] ?? $attachment['url'] ?? null;
                                    break;
                                }
                            }
                        }

                        $conversationSnippet = $ticket->channel === 'comment'
                            ? ($ticket->initial_message ?? $ticket->subject ?? $lastMessage?->message)
                            : ($lastMessage?->message ?? $ticket->subject);
                        $postLink = $ticket->channel === 'comment' ? $ticket->facebook_post_id : null;
                    @endphp

                    <div
                        class="conversation-item mx-2 mt-1 px-3 py-2 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent {{ $isUnread ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                        data-id="{{ $ticket->id }}"
                        data-unread-count="{{ $unreadCount }}"
                    >

                        <div class="flex items-start gap-2">

                            {{-- PROFILE --}}
                            <div class="relative shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center font-bold text-base shadow-sm">
                                    @if($ticket->channel === 'messenger')
                                        <i class="fab fa-facebook-messenger fa-lg text-white"></i>
                                    @elseif($ticket->channel === 'comment')
                                        <i class="fab fa-facebook fa-lg text-white"></i>
                                    @else
                                        {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}
                                    @endif
                                </div>
                                @if($isUnread)
                                    <div class="unread-indicator"></div>
                                @endif
                            </div>

                            {{-- CONTENT --}}
                            <div class="flex-1 min-w-0">

                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <h3 class="font-semibold {{ $isUnread ? 'text-gray-900 dark:text-gray-100' : 'text-gray-800 dark:text-gray-200' }} truncate text-sm">
                                        {{ $ticket->customer_name ?? $ticket->customer_facebook_id }}
                                    </h3>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="conversation-time text-[10px] {{ $isUnread ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-400 dark:text-gray-500' }} whitespace-nowrap">
                                            {{ $ticket->updated_at->format('h:i A') }}
                                        </span>
                                        @if($unreadCount > 0)
                                            <span class="conversation-unread-badge inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-blue-600 text-white text-[10px] font-semibold">
                                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                            </span>
                                        @endif
                                        <button type="button" onclick="event.stopPropagation(); toggleConversationMenu(event, {{ $ticket->id }})" class="conversation-menu-btn opacity-0 group-hover:opacity-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full p-1 transition-opacity">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p class="conversation-snippet text-xs {{ $isUnread ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-500 dark:text-gray-400' }} truncate leading-relaxed mb-1">
                                    {{ \Illuminate\Support\Str::limit($conversationSnippet, 80) }}
                                </p>

                                @if($postLink)
                                    <p class="text-[10px] text-blue-600 dark:text-blue-300 truncate leading-relaxed mb-1">
                                        <a href="https://www.facebook.com/{{ $postLink }}" target="_blank" rel="noopener noreferrer">View post</a>
                                    </p>
                                @endif

                                @if($imagePreview)
                                    <div class="mt-2">
                                        <button type="button" onclick="event.stopPropagation(); showImagePreview(@json($imagePreview))" class="group inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-2 py-1 shadow-sm hover:border-blue-300 dark:border-gray-700 dark:bg-gray-900 transition">
                                            <img src="{{ $imagePreview }}" alt="Image preview" class="h-12 w-12 rounded-xl object-cover" loading="lazy">
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 group-hover:text-blue-600">View image</span>
                                        </button>
                                    </div>
                                @endif

                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] font-medium text-[#1877f2]">
                                        {{ $ticket->facebookPage?->name }}
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- CONVERSATION MENU --}}
                    <div id="conversation-menu-{{ $ticket->id }}" class="conversation-menu hidden absolute right-2 top-8 z-50 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                        <button type="button" onclick="toggleConversationReadStatus({{ $ticket->id }}, {{ $isUnread ? 'true' : 'false' }})" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                            @if($isUnread)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 10l6 6 6-6"></path>
                                </svg>
                                Mark as read
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Mark as unread
                            @endif
                        </button>
                    </div>

                @empty

                    <div id="conversation-empty-state" class="h-full flex items-center justify-center p-10">

                        <div class="text-center">

                            <div class="h-24 w-24 rounded-full bg-[#e7f3ff] flex items-center justify-center text-5xl mx-auto mb-6">
                                💬
                            </div>

                            <h3 class="text-lg font-semibold text-gray-700 mb-2">
                                No Conversations
                            </h3>

                            <p class="text-sm text-gray-400">
                                No conversations available
                            </p>

                        </div>

                    </div>

                @endforelse

            </div>

        </div>

        {{-- CHAT AREA --}}
        <div
            class="flex-1 flex bg-[#f7f8fa] relative"
            id="main-content"
        >

            <button id="open-context-panel-button" onclick="toggleContextPanel()" class="hidden absolute right-4 top-4 z-20 inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                Details
            </button>

            {{-- CHAT CONTAINER --}}
            <div
                class="flex-1 flex flex-col"
                id="chat-area"
            >

            {{-- EMPTY STATE --}}
            <div class="h-full flex items-center justify-center">

                <div class="text-center">

                    <div class="h-24 w-24 rounded-full bg-[#e7f3ff] flex items-center justify-center text-5xl mx-auto mb-6">
                        💬
                    </div>

                    <h3 class="text-2xl font-bold text-gray-700 mb-2">

                        Select a Conversation

                    </h3>

                    <p class="text-gray-400">

                        Choose a conversation from the sidebar

                    </p>

                </div>

            </div>

        </div>

        {{-- CONTEXT PANEL --}}
        <div class="w-80 bg-white dark:bg-gray-900 border-l border-gray-100 dark:border-gray-700 flex flex-col shrink-0 transition-all duration-300 ease-in-out" id="context-panel">

            {{-- CONTEXT HEADER --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-900 z-20">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Conversation details</h3>
                    <button type="button" onclick="toggleContextPanel()" class="inline-flex items-center justify-center h-9 w-9 rounded-full border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white transition-colors" title="Toggle panel">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- CONTEXT CONTENT --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="context-content">

                {{-- SUMMARY CARD --}}
                <div class="rounded-3xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2 text-gray-800 dark:text-gray-100">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold">Summary</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Click Edit to update the summary inline.</p>
                            </div>
                        </div>
                        <div class="flex gap-2 items-center">
                            <button type="button" id="ai-summary-btn" onclick="generateAISummary()" class="rounded-full border border-transparent bg-white px-3 py-1 text-xs font-semibold text-purple-600 hover:bg-purple-50 dark:bg-gray-900 dark:text-purple-200 dark:hover:bg-purple-900 transition" title="Generate AI summary">
                                🤖 AI
                            </button>
                            <button type="button" id="edit-summary-btn" onclick="enableSummaryEdit()" class="rounded-full border border-transparent bg-white px-3 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50 dark:bg-gray-900 dark:text-blue-200 dark:hover:bg-blue-900 transition">
                                Edit
                            </button>
                        </div>
                    </div>
                    <div id="conversation-summary-view" class="min-h-[120px] rounded-2xl border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 leading-relaxed">
                        <div id="conversation-summary-text" class="whitespace-pre-wrap break-words text-sm text-gray-700 dark:text-gray-200">Write a summary for this conversation</div>
                    </div>
                    <textarea id="conversation-summary" placeholder="Write a summary for this conversation" class="hidden w-full min-h-[120px] resize-none rounded-2xl border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-900"></textarea>
                    <div id="summary-action-buttons" class="hidden mt-3 flex gap-2 justify-end">
                        <button type="button" onclick="cancelSummaryEdit()" class="rounded-full border border-gray-300 bg-white px-4 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">Cancel</button>
                        <button type="button" id="save-summary-btn" onclick="saveSummary(true)" class="rounded-full bg-blue-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">Save</button>
                    </div>
                </div>

                {{-- TAGS CARD --}}
                <div class="rounded-3xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2 text-gray-800 dark:text-gray-100">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold">Tags</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Choose from database tags and add multiple tags.</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-3">
                        <select id="conversation-tag-select" class="min-w-0 flex-1 rounded-full border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-900">
                            <option value="">Select a tag</option>
                        </select>
                        <button type="button" id="add-tag-btn" onclick="handleAddTag()" class="rounded-full border border-transparent bg-white px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 dark:bg-gray-900 dark:hover:bg-blue-800 transition">
                            Add
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm" id="conversation-tags">
                        <span class="text-xs text-gray-500 dark:text-gray-400">No tags yet</span>
                    </div>
                </div>

                {{-- CUSTOMER DETAILS CARD --}}
                <div class="rounded-3xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-3 text-gray-800 dark:text-gray-100">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8.966 8.966 0 0112 15c2.14 0 4.118.745 5.665 2.004M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold">Customer Details</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Contact, Facebook profile and ticket meta.</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400" id="customer-details">
                        <div>Select a conversation to view customer information</div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div id="image-preview-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative max-w-full max-h-full overflow-hidden rounded-3xl bg-black shadow-2xl">
            <button type="button" onclick="closeImagePreview()" class="absolute right-3 top-3 z-20 rounded-full bg-white/90 p-2 text-black hover:bg-white dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                ×
            </button>
            <img id="image-preview-full" src="" alt="Image preview" class="max-w-full max-h-[90vh] object-contain">
        </div>
    </div>

    <div id="ticket-notification" class="pointer-events-none fixed bottom-4 left-4 z-50 hidden max-w-sm overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-xl transition-all duration-300 ease-out dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-1">
            <div id="ticket-notification-title" class="text-sm font-semibold text-gray-900 dark:text-gray-100"></div>
            <div id="ticket-notification-ticket" class="text-xs text-gray-500 dark:text-gray-400"></div>
            <div id="ticket-notification-text" class="text-sm text-gray-700 dark:text-gray-200"></div>
        </div>
    </div>

    <script>

        let currentTicketId = null;
        let messageIds = new Set();
        let ticketChannelSubscriptions = {};
        let ticketNotificationIds = new Set();
        const MESSAGE_GAP_THRESHOLD_MINUTES = 30;

        document.addEventListener('DOMContentLoaded', function () {

            bindConversationEvents();
            subscribeToAllTicketChannels();
            subscribeToUserChannel();
            bindSummaryInput();

        });

        function bindConversationEvent(item)
        {
            item.addEventListener('click', function () {
                document.querySelectorAll('.conversation-item').forEach(el => {
                    el.classList.remove('selected');
                });

                this.classList.add('selected');
                const ticketId = this.dataset.id;
                loadConversation(ticketId);
            });
        }

        function bindConversationEvents()
        {
            const conversationItems = document.querySelectorAll('.conversation-item');
            conversationItems.forEach(item => bindConversationEvent(item));
        }

        async function loadConversation(ticketId)
        {

            try {

                currentTicketId = ticketId;

                document.getElementById('chat-area').innerHTML = `
                    <div class="h-full flex items-center justify-center">
                        <div class="text-gray-400 animate-pulse">
                            Loading conversation...
                        </div>
                    </div>
                `;

                const response = await fetch(`/tickets/${ticketId}`, {

                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }

                });

                const data = await response.json();

                document.getElementById('chat-area').innerHTML =
                    data.html;

                // Update context panel with ticket data
                updateContextPanel(data.ticket);

                bindReplyForm();
                bindTicketControls();
                initMessageIds();
                scrollMessagesToBottom();
                subscribeToTicketChannel(ticketId);
                await markTicketRead(ticketId);
                clearConversationUnread(document.querySelector(`.conversation-item[data-id="${ticketId}"]`));

                document.getElementById('agent_message')?.focus();

            } catch (error) {

                console.error(error);

            }

        }

        function bindReplyForm()
        {

            const form =
                document.getElementById('reply-form');
            const attachButton = document.getElementById('attach-file-btn');
            const fileInput = document.getElementById('reply-attachments');
            const attachmentPreview = document.getElementById('attachment-preview');

            if (!form) {
                return;
            }

            form.addEventListener('submit', function(e) {

                e.preventDefault();

                sendReply();

            });

            if (attachButton && fileInput) {
                attachButton.addEventListener('click', function() {
                    fileInput.click();
                });

                fileInput.addEventListener('change', function() {
                    updateAttachmentPreview(attachmentPreview, fileInput.files);
                });
            }

            // Handle Enter key to send message
            const textarea = document.getElementById('agent_message');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendReply();
                    }
                });

                // Auto-resize textarea
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 128) + 'px';
                });
            }

        }

        function updateAttachmentPreview(attachmentPreview, files)
        {
            if (!attachmentPreview) {
                return;
            }

            if (!files || files.length === 0) {
                attachmentPreview.textContent = '';
                return;
            }

            const names = Array.from(files).map(file => file.name);
            attachmentPreview.textContent = `Attached: ${names.join(', ')}`;
        }

        function bindTicketControls()
        {
            const status = document.getElementById('ticket-status');
            const priority = document.getElementById('ticket-priority');
            const agent = document.getElementById('ticket-agent');

            if (!status || !priority || !agent) {
                return;
            }

            status.addEventListener('change', updateTicketMeta);
            priority.addEventListener('change', updateTicketMeta);
            agent.addEventListener('change', updateTicketMeta);
        }

        async function updateTicketMeta()
        {
            if (!currentTicketId) {
                return;
            }

            const status = document.getElementById('ticket-status')?.value;
            const priority = document.getElementById('ticket-priority')?.value;
            const assignedTo = document.getElementById('ticket-agent')?.value;

            try {
                const response = await fetch(
                    `/tickets/${currentTicketId}`,
                    {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            status,
                            priority,
                            assigned_to: assignedTo === '' ? null : assignedTo
                        })
                    }
                );

                const data = await response.json();

                if (data.status === 'success') {
                    console.log('Ticket updated successfully');
                } else {
                    console.error('Unable to update ticket');
                }
            } catch (error) {
                console.error(error);
                console.error('Unable to update ticket');
            }
        }

        async function sendReply()
        {

            if (!currentTicketId) {
                return;
            }

            const textarea =
                document.getElementById('agent_message');

            const message = textarea.value.trim();
            const fileInput = document.getElementById('reply-attachments');
            const files = fileInput?.files || [];

            if (!message && files.length === 0) {
                return;
            }

            try {

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PUT');
                formData.append('agent_message', message);

                Array.from(files).forEach((file, index) => {
                    formData.append('attachments[]', file);
                });

                const response = await fetch(
                    `/tickets/${currentTicketId}`,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    }
                );

                const data = await response.json();

                if (data.status === 'success') {

                    textarea.value = '';
                    textarea.style.height = 'auto';
                    if (fileInput) {
                        fileInput.value = '';
                        updateAttachmentPreview(document.getElementById('attachment-preview'), []);
                    }

                    appendMessage(data.chat_message);

                    // Update sidebar conversation
                    const ticketItem = document.querySelector(`.conversation-item[data-id="${currentTicketId}"]`);
                    if (ticketItem) {
                        updateSidebarConversation(ticketItem, data.chat_message);
                    }

                }

            } catch (error) {

                console.error(error);

            }

        }

        function appendMessage(message)
        {
            const container = document.getElementById('messages-container');
            const messageId = message?.id ? String(message.id) : (message?.created_at ? `temp-${message.created_at}` : null);

            console.log('appendMessage called with:', { message, attachments: message?.attachments });

            if (!container || !message || !messageId || messageIds.has(messageId)) {
                return;
            }

            const messagesWrapper = container.querySelector('.max-w-2xl') || container;
            const lastMessageElement = messagesWrapper.querySelector('[data-message-id]:last-child');
            const lastMessageType = lastMessageElement?.dataset.messageType;
            const lastAgentId = lastMessageElement?.dataset.agentId;
            const lastCreatedAt = lastMessageElement?.dataset.createdAt ? new Date(lastMessageElement.dataset.createdAt) : null;
            const currentCreatedAt = new Date(message.created_at);

            const isAgentMessage = message.message_type === 'agent' || message.sender_type === 'agent';
            const pageName = message.facebook_page_name || 'Agent';
            const customerName = message.customer_name || 'U';
            const agentName = message.agent_name || 'Agent';
            const avatar = customerName.charAt(0).toUpperCase();
            const currentAgentId = String(message.user_id || '');

            const showMeta = !lastMessageElement ||
                lastMessageType !== message.message_type ||
                (lastCreatedAt && ((currentCreatedAt - lastCreatedAt) / 60000) > MESSAGE_GAP_THRESHOLD_MINUTES) ||
                (isAgentMessage && currentAgentId !== lastAgentId);
            const showTime = !lastMessageElement ||
                !lastCreatedAt ||
                ((currentCreatedAt - lastCreatedAt) / 60000) > 5;

            messageIds.add(messageId);

            console.log('Agent message debug:', { isAgentMessage, agentName, message });

            let messageHTML = '';

            if (isAgentMessage) {
                messageHTML = `
                    <div class="flex justify-end" data-message-id="${message.id}" data-message-type="agent" data-agent-id="${currentAgentId}" data-created-at="${message.created_at}">
                        <div class="flex flex-col gap-1 max-w-[70%]">
                            <div class="px-4 py-2.5 rounded-2xl rounded-br-md bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white shadow-sm">
                                <p class="text-sm leading-relaxed break-words">
                                    ${escapeHtml(message.message)}
                                </p>
                                ${renderAttachments(message.attachments)}
                            </div>
                            ${showMeta ? `
                                <div class="text-xs flex justify-end items-center gap-2 mt-1">
                                    <span class="text-gray-400 dark:text-gray-500">${escapeHtml(agentName)}</span>
                                </div>
                            ` : ''}
                            ${showTime ? `
                                <span class="text-xs text-gray-200 text-right px-2">
                                    ${formatTime(message.created_at)}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                `;
            } else {
                messageHTML = `
                    <div class="flex justify-start" data-message-id="${message.id}" data-message-type="customer" data-created-at="${message.created_at}">
                        <div class="flex items-end gap-2 max-w-[70%]">
                            ${showMeta ? `
                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                    ${escapeHtml(avatar)}
                                </div>
                            ` : '<div class="w-8"></div>'}
                            <div class="flex flex-col gap-1">
                                ${showMeta ? `
                                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        ${escapeHtml(customerName)}
                                    </div>
                                ` : ''}
                                <div class="px-4 py-2.5 rounded-2xl rounded-bl-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 shadow-sm border border-gray-200 dark:border-gray-600">
                                    <p class="text-sm leading-relaxed break-words">
                                        ${escapeHtml(message.message)}
                                    </p>
                                    ${renderAttachments(message.attachments)}
                                </div>
                                ${showTime ? `
                                    <span class="text-xs text-gray-500 dark:text-gray-400 px-2">
                                        ${formatTime(message.created_at)}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            messagesWrapper.innerHTML += messageHTML;
            scrollMessagesToBottom();
        }

        function scrollMessagesToBottom()
        {

            const container =
                document.getElementById('messages-container');

            if (!container) {
                return;
            }

            setTimeout(() => {

                container.scrollTop =
                    container.scrollHeight;

            }, 50);

        }

        function renderAttachments(attachments)
        {
            if (!attachments) {
                return '';
            }

            const attachmentArray = Array.isArray(attachments)
                ? attachments
                : (typeof attachments === 'object' ? Object.values(attachments) : []);

            if (attachmentArray.length === 0) {
                return '';
            }

            let html = '';
            attachmentArray.forEach(attachment => {
                if (!attachment || !attachment.type) {
                    return;
                }

                const url = escapeHtml(attachment.payload?.url ?? attachment.url ?? '');
                if (!url) {
                    return;
                }

                try {
                    if (attachment.type === 'image') {
                        html += `
                            <img
                                src="${url}"
                                alt="Conversation image"
                                class="max-w-full rounded-xl border border-gray-200 dark:border-gray-600 mt-2 shadow-sm cursor-pointer hover:opacity-90"
                                style="max-height:240px;"
                                loading="lazy"
                                onclick="showImagePreview('${url}')"
                                onerror="this.style.display='none'"
                            >
                        `;
                    } else if (attachment.type === 'video') {
                        html += `
                            <div class="mt-2 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-600">
                                <video controls class="w-full">
                                    <source src="${url}" type="video/mp4">
                                </video>
                            </div>
                        `;
                    } else if (attachment.type === 'audio') {
                        html += `
                            <audio controls class="mt-2 w-full">
                                <source src="${url}" type="audio/mpeg">
                            </audio>
                        `;
                    } else if (attachment.type === 'file' && url.includes('.pdf')) {
                        html += `
                            <a href="${url}" target="_blank" class="text-blue-600 dark:text-blue-400 underline mt-2 block">
                                Download PDF
                            </a>
                        `;
                    }
                } catch (e) {
                    console.error('Error rendering attachment:', attachment, e);
                }
            });

            return html ? `<div class="mt-2 grid gap-2">${html}</div>` : '';
        }

        function formatTime(dateString)
        {

            const date = new Date(dateString);

            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

        }

        function showImagePreview(url)
        {
            const modal = document.getElementById('image-preview-modal');
            const previewImage = document.getElementById('image-preview-full');
            if (!modal || !previewImage || !url) {
                return;
            }
            previewImage.src = url;
            modal.classList.remove('hidden');
        }

        function closeImagePreview()
        {
            const modal = document.getElementById('image-preview-modal');
            const previewImage = document.getElementById('image-preview-full');
            if (!modal || !previewImage) {
                return;
            }
            modal.classList.add('hidden');
            previewImage.src = '';
        }

        function showFilesModal()
        {
            const modal = document.getElementById('files-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeFilesModal()
        {
            const modal = document.getElementById('files-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function toggleContextPanel()
        {
            const panel = document.getElementById('context-panel');
            const icon = panel.querySelector('svg');
            const openButton = document.getElementById('open-context-panel-button');
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                icon.style.transform = 'rotate(0deg)';
                if (openButton) {
                    openButton.classList.add('hidden');
                }
            } else {
                panel.classList.add('hidden');
                icon.style.transform = 'rotate(180deg)';
                if (openButton) {
                    openButton.classList.remove('hidden');
                }
            }
        }

        function updateContextPanel(ticketData)
        {
            if (!ticketData) return;

            // Update summary
            const summaryEl = document.getElementById('conversation-summary');
            const summaryText = document.getElementById('conversation-summary-text');
            const summaryValue = ticketData.summary || '';
            if (summaryEl) {
                summaryEl.value = summaryValue;
                summaryEl.dataset.originalValue = summaryValue;
            }
            if (summaryText) {
                summaryText.textContent = summaryValue || 'Write a summary for this conversation';
            }

            // Update tags
            availableTags = ticketData.available_tags || [];
            selectedTags = ticketData.tags || [];
            renderTagOptions();
            renderSelectedTags();

            // Update customer details
            const customerEl = document.getElementById('customer-details');
            if (ticketData.customer) {
                const customer = ticketData.customer;
                customerEl.innerHTML = `
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                            ${customer.name ? customer.name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 dark:text-gray-100 truncate">${escapeHtml(customer.name || 'Unknown')}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${escapeHtml(customer.email || 'No email')}</div>
                        </div>
                    </div>
                    ${customer.phone ? `<div class="text-sm text-gray-600 dark:text-gray-300"><strong>Phone:</strong> ${escapeHtml(customer.phone)}</div>` : ''}
                    ${customer.location ? `<div class="text-sm text-gray-600 dark:text-gray-300"><strong>Location:</strong> ${escapeHtml(customer.location)}</div>` : ''}
                    <div class="text-sm text-gray-600 dark:text-gray-300"><strong>Created:</strong> ${new Date(ticketData.created_at).toLocaleDateString()}</div>
                `;
            } else {
                customerEl.innerHTML = '<div class="text-sm text-gray-500 dark:text-gray-400">Customer information not available</div>';
            }
        }

        let availableTags = [];
        let selectedTags = [];

        function bindSummaryInput()
        {
            const summaryEl = document.getElementById('conversation-summary');

            if (!summaryEl) {
                return;
            }

            summaryEl.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    summaryEl.value = summaryEl.dataset.originalValue || '';
                    disableSummaryEdit();
                }
                if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
                    e.preventDefault();
                    saveSummary(true);
                }
            });

            summaryEl.addEventListener('blur', function () {
                saveSummary();
            });
        }

        function renderTagOptions()
        {
            const select = document.getElementById('conversation-tag-select');
            if (!select) {
                return;
            }

            const currentIds = selectedTags.map(tag => tag.id);
            select.innerHTML = `
                <option value="">Select a tag</option>
                ${availableTags.map(tag => `
                    <option value="${tag.id}" ${currentIds.includes(tag.id) ? 'disabled' : ''}>
                        ${escapeHtml(tag.name)}
                    </option>
                `).join('')}
            `;
        }

        function renderSelectedTags()
        {
            const tagsEl = document.getElementById('conversation-tags');
            if (!tagsEl) {
                return;
            }

            if (!selectedTags || selectedTags.length === 0) {
                tagsEl.innerHTML = '<span class="text-xs text-gray-500 dark:text-gray-400">No tags yet</span>';
                return;
            }

            tagsEl.innerHTML = selectedTags.map(tag => `
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ${escapeHtml(tag.name)}
                    <button type="button" onclick="removeTagFromTicket(${tag.id})" class="rounded-full p-1 text-blue-700 hover:bg-blue-200 dark:text-blue-200 dark:hover:bg-blue-800">
                        &times;
                    </button>
                </span>
            `).join('');
        }

        function handleAddTag()
        {
            const select = document.getElementById('conversation-tag-select');
            if (!select || !select.value) {
                return;
            }

            const tagId = Number(select.value);
            const tag = availableTags.find(tag => tag.id === tagId);
            if (!tag || selectedTags.some(existing => existing.id === tagId)) {
                return;
            }

            selectedTags.push(tag);
            renderTagOptions();
            renderSelectedTags();
            saveTags();
        }

        async function saveTags()
        {
            if (!currentTicketId) {
                return;
            }

            const tagIds = selectedTags.map(tag => tag.id);
            try {
                await fetch(`/tickets/${currentTicketId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ tags: tagIds })
                });
            } catch (error) {
                console.error('Unable to save tags', error);
            }
        }

        function removeTagFromTicket(tagId)
        {
            selectedTags = selectedTags.filter(tag => tag.id !== tagId);
            renderTagOptions();
            renderSelectedTags();
            saveTags();
        }

        function enableSummaryEdit()
        {
            const summaryEl = document.getElementById('conversation-summary');
            const summaryView = document.getElementById('conversation-summary-view');
            const editButton = document.getElementById('edit-summary-btn');
            const actionButtons = document.getElementById('summary-action-buttons');

            if (!summaryEl || !summaryView || !editButton || !actionButtons) {
                return;
            }

            summaryView.classList.add('hidden');
            editButton.classList.add('hidden');
            summaryEl.classList.remove('hidden');
            actionButtons.classList.remove('hidden');
            summaryEl.focus();
        }

        function disableSummaryEdit()
        {
            const summaryEl = document.getElementById('conversation-summary');
            const summaryView = document.getElementById('conversation-summary-view');
            const editButton = document.getElementById('edit-summary-btn');
            const actionButtons = document.getElementById('summary-action-buttons');

            if (!summaryEl || !summaryView || !editButton || !actionButtons) {
                return;
            }

            summaryEl.classList.add('hidden');
            summaryView.classList.remove('hidden');
            editButton.classList.remove('hidden');
            actionButtons.classList.add('hidden');
        }

        function cancelSummaryEdit()
        {
            const summaryEl = document.getElementById('conversation-summary');
            const originalValue = summaryEl?.dataset.originalValue || '';
            if (summaryEl) {
                summaryEl.value = originalValue;
            }
            disableSummaryEdit();
        }

        async function saveSummary(force = false)
        {
            if (!currentTicketId) {
                return;
            }

            const summaryEl = document.getElementById('conversation-summary');
            const summaryText = document.getElementById('conversation-summary-text');
            if (!summaryEl || !summaryText) {
                return;
            }

            const originalValue = summaryEl.dataset.originalValue || '';
            const summary = summaryEl.value.trim();

            if (!force && summary === originalValue) {
                disableSummaryEdit();
                return;
            }

            try {
                const response = await fetch(`/tickets/${currentTicketId}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ summary })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    summaryEl.dataset.originalValue = summary;
                    summaryText.textContent = summary || 'Write a summary for this conversation';
                    disableSummaryEdit();
                } else {
                    console.error('Unable to save summary');
                }
            } catch (error) {
                console.error('Unable to save summary', error);
            }
        }

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('image-preview-modal');
            if (!modal || modal.classList.contains('hidden')) {
                return;
            }
            if (event.target === modal) {
                closeImagePreview();
            }
        });

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('files-modal');
            if (!modal || modal.classList.contains('hidden')) {
                return;
            }
            if (event.target === modal) {
                closeFilesModal();
            }
        });

        function escapeHtml(unsafe)
        {
            if (unsafe === undefined || unsafe === null) {
                return '';
            }

            return String(unsafe)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function subscribeToTicketChannel(ticketId)
        {
            if (!ticketId || !window.Echo) {
                return;
            }

            if (ticketChannelSubscriptions[ticketId]) {
                return;
            }

            const channel = window.Echo.private('ticket.' + ticketId)
                .listen('.NewTicketMessage', handleTicketMessageEvent)
                .error((error) => {
                    console.error('WebSocket error:', error);
                });

            ticketChannelSubscriptions[ticketId] = channel;
        }

        function subscribeToAllTicketChannels()
        {
            document.querySelectorAll('.conversation-item').forEach(item => {
                const ticketId = item.dataset.id;
                subscribeToTicketChannel(ticketId);
            });
        }

        function subscribeToUserChannel()
        {
            const userId = @json(auth()->id());
            const isAdmin = @json(auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager'));

            if (!userId || !window.Echo) {
                return;
            }

            window.Echo.private(`App.Models.User.${userId}`)
                .listen('.NewTicketMessage', handleTicketMessageEvent)
                .error((error) => console.error('User channel WebSocket error:', error));

            if (isAdmin) {
                window.Echo.private('tickets.new')
                    .listen('.NewTicketMessage', handleTicketMessageEvent)
                    .error((error) => console.error('Tickets new channel WebSocket error:', error));
            }
        }

        function handleTicketMessageEvent(event)
        {
            console.log('Received WebSocket event:', event);
            const payload = event?.data ? event.data : event;
            const ticketId = payload?.ticket_id ?? payload?.ticketId ?? payload?.ticket?.id;
            const message = payload?.message ?? payload?.data?.message;
            const ticket = payload?.ticket ?? event?.ticket ?? payload?.data?.ticket;

            if (!ticketId || !message) {
                return;
            }

            const messageData = {
                ...message,
                sender_type: message.sender_type || message.message_type,
                message_type: message.message_type || message.sender_type,
                message: message.message || message.text || message.content || message.body || '',
                attachments: message.attachments ?? [],
                created_at: message.created_at || new Date().toISOString()
            };

            const ticketItem = document.querySelector(`.conversation-item[data-id="${ticketId}"]`);

            if (!ticketItem && ticket) {
                appendConversationItem(ticket);
            }

            const currentTicketItem = document.querySelector(`.conversation-item[data-id="${ticketId}"]`);
            if (currentTicketItem) {
                updateSidebarConversation(currentTicketItem, messageData);
            }

            if (messageData.sender_type !== 'agent') {
                if (!messageData.id || !ticketNotificationIds.has(messageData.id)) {
                    showTicketNotification(
                        `New message from ${currentTicketItem ? currentTicketItem.querySelector('h3')?.textContent.trim() : 'customer'}`,
                        messageData.message
                    );
                    if (messageData.id) {
                        ticketNotificationIds.add(messageData.id);
                    }
                }
            }

            if (String(ticketId) === String(currentTicketId)) {
                appendMessage(messageData);
                clearConversationUnread(currentTicketItem);
            }
        }

        function showTicketNotification(title, message)
        {
            const notification = document.getElementById('ticket-notification');
            const titleEl = document.getElementById('ticket-notification-title');
            const ticketEl = document.getElementById('ticket-notification-ticket');
            const textEl = document.getElementById('ticket-notification-text');

            if (!notification || !titleEl || !ticketEl || !textEl) {
                return;
            }

            titleEl.textContent = title;
            ticketEl.textContent = '';
            textEl.textContent = message;

            notification.classList.remove('hidden', 'opacity-0', 'translate-y-4');
            notification.classList.add('opacity-100', 'translate-y-0');

            clearTimeout(window.ticketNotificationTimeout);
            window.ticketNotificationTimeout = setTimeout(() => {
                notification.classList.remove('opacity-100');
                notification.classList.add('opacity-0', 'translate-y-4');
                setTimeout(() => notification.classList.add('hidden'), 300);
            }, 4000);
        }

        function getOrCreateUnreadBadge(item)
        {
            let badge = item.querySelector('.conversation-unread-badge');
            if (!badge) {
                const metaWrapper = item.querySelector('.flex.items-center.gap-2.shrink-0');
                badge = document.createElement('span');
                badge.className = 'conversation-unread-badge inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-blue-600 text-white text-[10px] font-semibold';
                badge.style.display = 'none';
                badge.dataset.count = '0';
                badge.textContent = '';
                if (metaWrapper) {
                    metaWrapper.appendChild(badge);
                }
            }
            return badge;
        }

        function renderConversationItem(ticket)
        {
            const unreadCount = ticket.unread_count ?? 1;
            const time = ticket.updated_at ? formatTime(ticket.updated_at) : '';
            const title = ticket.customer_name || ticket.customer_facebook_id || 'Unknown';
            const channel = ticket.channel || ticket.latest_message?.channel || ticket.latestMessage?.channel || 'unknown';
            const snippet = channel === 'comment'
                ? (ticket.initial_message || ticket.subject || 'New comment')
                : (ticket.subject || 'New ticket');
            const pageName = ticket.facebook_page_name ? `<span class="text-[10px] font-medium text-[#1877f2]">${escapeHtml(ticket.facebook_page_name)}</span>` : '';
            const postLink = ticket.post_link || (ticket.facebook_post_id ? `https://www.facebook.com/${escapeHtml(ticket.facebook_post_id)}` : null);

            const attachments = ticket.latest_message?.attachments || ticket.latestMessage?.attachments || ticket.attachments || [];
            const imageAttachment = Array.isArray(attachments) ? attachments.find(a => a?.type === 'image') : null;
            const imagePreviewBlock = imageAttachment ? `
                <button type="button" onclick="event.stopPropagation(); showImagePreview('${escapeHtml(imageAttachment.payload?.url || imageAttachment.url || '')}')" class="mt-2 inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-2 py-1 shadow-sm hover:border-blue-300 dark:border-gray-700 dark:bg-gray-900 transition">
                    <img src="${escapeHtml(imageAttachment.payload?.url || imageAttachment.url || '')}" alt="Image preview" class="h-12 w-12 rounded-xl object-cover" loading="lazy">
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">View image</span>
                </button>
            ` : '';
            const channelIcon = channel === 'messenger'
                ? '<i class="fab fa-facebook-messenger fa-lg text-white"></i>'
                : channel === 'comment'
                    ? '<i class="fab fa-facebook fa-lg text-white"></i>'
                    : escapeHtml((ticket.customer_name || ticket.customer_facebook_id || 'U').charAt(0).toUpperCase());

            return `
                <div class="conversation-item mx-2 mt-1 px-3 py-2 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent bg-blue-50 dark:bg-blue-900/20"
                    data-id="${ticket.id}"
                    data-unread-count="${unreadCount}">
                    <div class="flex items-start gap-2">
                        <div class="relative shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center font-bold text-base shadow-sm">
                                ${channelIcon}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate text-sm">${escapeHtml(title)}</h3>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="conversation-time text-[10px] text-gray-400 dark:text-gray-500 whitespace-nowrap">${time}</span>
                                    ${unreadCount > 0 ? `<span class="conversation-unread-badge inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-blue-600 text-white text-[10px] font-semibold">${unreadCount > 99 ? '99+' : unreadCount}</span>` : ''}
                                </div>
                            </div>
                            <p class="conversation-snippet text-xs text-gray-500 dark:text-gray-400 truncate leading-relaxed mb-1">${escapeHtml(snippet)}</p>
                            ${postLink ? `<p class="text-[10px] text-blue-600 dark:text-blue-300 truncate leading-relaxed mb-1"><a href="${escapeHtml(postLink)}" target="_blank" rel="noopener noreferrer">View post</a></p>` : ''}
                            ${imagePreviewBlock}
                            <div class="flex items-center gap-1">${pageName}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function appendConversationItem(ticket)
        {
            const existingItem = document.querySelector(`.conversation-item[data-id="${ticket.id}"]`);
            if (existingItem) {
                moveConversationItemToTop(existingItem);
                return;
            }

            const container = document.querySelector('.flex-1.overflow-y-auto.py-2');
            if (!container) {
                return;
            }

            const emptyState = document.getElementById('conversation-empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            container.insertAdjacentHTML('afterbegin', renderConversationItem(ticket));
            const newItem = document.querySelector(`.conversation-item[data-id="${ticket.id}"]`);
            if (newItem) {
                bindConversationEvent(newItem);
                subscribeToTicketChannel(ticket.id);
            }
        }

        function moveConversationItemToTop(item)
        {
            if (!item || !item.parentElement) {
                return;
            }

            const container = item.parentElement;
            const firstItem = container.firstElementChild;
            if (firstItem && firstItem !== item) {
                container.insertBefore(item, firstItem);
            }
        }

        function updateSidebarConversation(item, message)
        {
            const snippet = item.querySelector('.conversation-snippet');
            const time = item.querySelector('.conversation-time');
            const badge = getOrCreateUnreadBadge(item);
            const indicator = item.querySelector('.unread-indicator');
            const unreadCount = parseInt(item.dataset.unreadCount || '0', 10);
            const nextCount = message.sender_type !== 'agent' ? unreadCount + 1 : unreadCount;

            if (snippet) {
                const messageText = message.message || message.text || message.content || message.body || '';
                snippet.textContent = messageText;
            }

            if (time) {
                time.textContent = formatTime(message.created_at || message.created_at || new Date());
            }

            if (message.sender_type !== 'agent' && badge) {
                badge.dataset.count = nextCount;
                badge.textContent = nextCount > 99 ? '99+' : nextCount;
                if (nextCount > 0) {
                    badge.classList.remove('hidden');
                    badge.style.display = 'inline-flex';
                    item.classList.add('bg-blue-50', 'dark:bg-blue-900/20');

                    // Show unread indicator dot
                    if (indicator) {
                        indicator.style.display = 'block';
                    }

                    // Update text colors to unread state
                    const title = item.querySelector('h3');
                    if (title) {
                        title.classList.remove('text-gray-800', 'dark:text-gray-200');
                        title.classList.add('text-gray-900', 'dark:text-gray-100');
                    }

                    if (time) {
                        time.classList.remove('text-gray-400', 'dark:text-gray-500');
                        time.classList.add('text-gray-900', 'dark:text-gray-100', 'font-medium');
                    }

                    if (snippet) {
                        snippet.classList.remove('text-gray-500', 'dark:text-gray-400');
                        snippet.classList.add('text-gray-900', 'dark:text-gray-100', 'font-medium');
                    }
                } else {
                    badge.classList.add('hidden');
                    badge.style.display = 'none';
                    item.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');

                    // Hide unread indicator dot
                    if (indicator) {
                        indicator.style.display = 'none';
                    }
                }
                item.dataset.unreadCount = nextCount;
            }

            moveConversationItemToTop(item);
        }

        function clearConversationUnread(item)
        {
            if (!item) {
                return;
            }

            const badge = item.querySelector('.conversation-unread-badge');
            const indicator = item.querySelector('.unread-indicator');

            if (badge) {
                badge.dataset.count = '0';
                badge.textContent = '';
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }

            if (indicator) {
                indicator.style.display = 'none';
            }

            item.dataset.unreadCount = '0';
            item.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');

            // Update text colors to read state
            const title = item.querySelector('h3');
            const time = item.querySelector('.conversation-time');
            const snippet = item.querySelector('.conversation-snippet');

            if (title) {
                title.classList.remove('text-gray-900', 'dark:text-gray-100');
                title.classList.add('text-gray-800', 'dark:text-gray-200');
            }

            if (time) {
                time.classList.remove('text-gray-900', 'dark:text-gray-100', 'font-medium');
                time.classList.add('text-gray-400', 'dark:text-gray-500');
            }

            if (snippet) {
                snippet.classList.remove('text-gray-900', 'dark:text-gray-100', 'font-medium');
                snippet.classList.add('text-gray-500', 'dark:text-gray-400');
            }
        }

        function initMessageIds()
        {
            messageIds.clear();
            document.querySelectorAll('[data-message-id]').forEach(el => {
                messageIds.add(el.dataset.messageId);
            });
        }

        async function markTicketRead(ticketId)
        {
            if (!ticketId || !window.fetch) {
                return;
            }

            try {
                await fetch(`/tickets/${ticketId}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });
            } catch (error) {
                console.error('Unable to mark ticket read', error);
            }
        }

        async function markTicketUnread(ticketId)
        {
            if (!ticketId || !window.fetch) {
                return;
            }

            try {
                const response = await fetch(`/tickets/${ticketId}/mark-unread`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });

                const data = await response.json();
                const ticketItem = document.querySelector(`.conversation-item[data-id="${ticketId}"]`);
                if (!ticketItem) {
                    return;
                }

                ticketItem.dataset.unreadCount = data.unread_count ?? 0;
                const badge = getOrCreateUnreadBadge(ticketItem);

                if (badge) {
                    const unreadCount = data.unread_count ?? 0;
                    badge.dataset.count = unreadCount;
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;

                    if (unreadCount > 0) {
                        badge.classList.remove('hidden');
                        badge.style.display = 'inline-flex';
                        ticketItem.classList.add('bg-blue-50', 'dark:bg-blue-900/20');

                        // Show unread indicator dot
                        const indicator = ticketItem.querySelector('.unread-indicator');
                        if (indicator) {
                            indicator.style.display = 'block';
                        }

                        // Update text colors to unread state
                        const title = ticketItem.querySelector('h3');
                        const time = ticketItem.querySelector('.conversation-time');
                        const snippet = ticketItem.querySelector('.conversation-snippet');

                        if (title) {
                            title.classList.remove('text-gray-800', 'dark:text-gray-200');
                            title.classList.add('text-gray-900', 'dark:text-gray-100');
                        }

                        if (time) {
                            time.classList.remove('text-gray-400', 'dark:text-gray-500');
                            time.classList.add('text-gray-900', 'dark:text-gray-100', 'font-medium');
                        }

                        if (snippet) {
                            snippet.classList.remove('text-gray-500', 'dark:text-gray-400');
                            snippet.classList.add('text-gray-900', 'dark:text-gray-100', 'font-medium');
                        }
                    } else {
                        badge.classList.add('hidden');
                        badge.style.display = 'none';
                        ticketItem.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');

                        // Hide unread indicator dot
                        const indicator = ticketItem.querySelector('.unread-indicator');
                        if (indicator) {
                            indicator.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Unable to mark ticket unread', error);
            }
        }

        function toggleConversationMenu(event, ticketId)
        {
            event.stopPropagation();

            // Close all other menus first
            document.querySelectorAll('.conversation-menu').forEach(menu => {
                if (menu.id !== `conversation-menu-${ticketId}`) {
                    menu.classList.add('hidden');
                }
            });

            // Toggle the clicked menu
            const menu = document.getElementById(`conversation-menu-${ticketId}`);
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        async function markConversationUnread(ticketId)
        {
            // Close the menu
            const menu = document.getElementById(`conversation-menu-${ticketId}`);
            if (menu) {
                menu.classList.add('hidden');
            }

            // Mark as unread
            await markTicketUnread(ticketId);
        }

        async function toggleConversationReadStatus(ticketId, isCurrentlyUnread)
        {
            // Close the menu
            const menu = document.getElementById(`conversation-menu-${ticketId}`);
            if (menu) {
                menu.classList.add('hidden');
            }

            if (isCurrentlyUnread) {
                // Mark as read
                await markTicketRead(ticketId);
            } else {
                // Mark as unread
                await markTicketUnread(ticketId);
            }
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.conversation-menu-btn')) {
                document.querySelectorAll('.conversation-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Generate AI Summary
        async function generateAISummary()
        {
            if (!currentTicketId) {
                alert('No ticket selected');
                return;
            }

            const btn = document.getElementById('ai-summary-btn');
            const originalText = btn.innerHTML;

            // Show loading state
            btn.innerHTML = '⏳ Generating...';
            btn.disabled = true;

            try {
                const response = await fetch(`/tickets/${currentTicketId}/ai-summary`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Enable edit mode
                    enableSummaryEdit();

                    // Fill textarea with AI-generated summary
                    const summaryEl = document.getElementById('conversation-summary');
                    if (summaryEl) {
                        summaryEl.value = data.summary;
                        summaryEl.focus();
                    }
                } else {
                    alert('Failed to generate summary: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error generating AI summary:', error);
                alert('Error generating AI summary. Please try again.');
            } finally {
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Get AI Suggestions for reply
        async function getAISuggestions(ticketId) {
            const btn = document.getElementById('replay-suggest-btn');
            const originalText = btn.innerHTML;

            // Show loading state
            btn.innerHTML = '⏳ Generating...';
            btn.disabled = true;

            try {
                const response = await fetch(`/tickets/${ticketId}/ai-suggestions`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                console.log('AI suggestions response:', data);

                if (data.success) {
                    displayAISuggestions(data.suggestions);
                } else {
                    alert('Failed to get AI suggestions: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error getting AI suggestions:', error);
                alert('Error getting AI suggestions. Please try again.');
            } finally {
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Display AI suggestions
        function displayAISuggestions(suggestions) {
            const container = document.getElementById('ai-suggestions');
            const list = document.getElementById('suggestions-list');

            list.innerHTML = '';

            suggestions.forEach((suggestion, index) => {
                const suggestionEl = document.createElement('div');
                suggestionEl.className = 'p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-md cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors';
                suggestionEl.onclick = () => selectSuggestion(suggestion);

                suggestionEl.innerHTML = `
                    <div class="flex items-start justify-between">
                        <div class="flex-1 text-sm text-gray-900 dark:text-gray-100">
                            ${escapeHtml(suggestion)}
                        </div>
                        <div class="ml-2 text-xs text-purple-600 dark:text-purple-400 font-medium">
                            Click to use
                        </div>
                    </div>
                `;

                list.appendChild(suggestionEl);
            });

            container.classList.remove('hidden');
        }

        // Select a suggestion and put it in the textarea
        function selectSuggestion(suggestion) {
            const textarea = document.getElementById('agent_message');
            textarea.value = suggestion;

            const container = document.getElementById('ai-suggestions');
            const list = document.getElementById('suggestions-list');

            // Clear suggestions after selection so the user cannot pick another
            list.innerHTML = '';
            container.classList.add('hidden');
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

    </script>

</x-layouts.app>
