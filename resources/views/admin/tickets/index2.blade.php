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
        <div class="w-[380px] bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 flex flex-col shrink-0">

            {{-- HEADER --}}
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-20">

                <div class="mb-4">

                    <div>

                        <h2 class="text-[24px] font-bold text-gray-800 dark:text-gray-100 leading-tight">
                            Inbox
                        </h2>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Respond to messages, set up automations and more.
                        </p>

                    </div>

                </div>

                {{-- FILTERS --}}
                <div class="mb-4">
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="sidebar-filter-chip">Unread</span>
                        <span class="sidebar-filter-chip">Priority</span>
                        <span class="sidebar-filter-chip">Ad replies</span>
                        <span class="sidebar-filter-chip">Follow up</span>
                    </div>
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
                                    {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}
                                </div>
                                @if($isUnread)
                                    <div class="unread-indicator"></div>
                                @endif
                                <div class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-[8px] shadow-sm">
                                    💬
                                </div>
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
                                    </div>
                                </div>

                                <p class="conversation-snippet text-xs {{ $isUnread ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-500 dark:text-gray-400' }} truncate leading-relaxed mb-1">
                                    {{ $lastMessage?->message ?? $ticket->subject }}
                                </p>

                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] font-medium text-[#1877f2]">
                                        {{ $ticket->facebookPage?->name }}
                                    </span>
                                    
                                </div>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="h-full flex items-center justify-center p-10">

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
            class="flex-1 flex flex-col bg-[#f7f8fa]"
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

        document.addEventListener('DOMContentLoaded', function () {

            bindConversationEvents();
            subscribeToAllTicketChannels();

        });

        function bindConversationEvents()
        {

            const conversationItems =
                document.querySelectorAll('.conversation-item');

            conversationItems.forEach(item => {

                item.addEventListener('click', function () {

                    document.querySelectorAll('.conversation-item')
                        .forEach(el => {

                            el.classList.remove('selected');

                        });

                    this.classList.add('selected');

                    const ticketId = this.dataset.id;

                    loadConversation(ticketId);

                });

            });

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

                bindReplyForm();
                bindTicketControls();
                bindUnreadToggle();
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

            if (!form) {
                return;
            }

            form.addEventListener('submit', function(e) {

                e.preventDefault();

                sendReply();

            });

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

            const message =
                textarea.value.trim();

            if (!message) {
                return;
            }

            try {

                const response = await fetch(
                    `/tickets/${currentTicketId}`,
                    {
                        method: 'PUT',

                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        body: JSON.stringify({
                            agent_message: message
                        })
                    }
                );

                const data = await response.json();

                if (data.status === 'success') {

                    textarea.value = '';

                    appendMessage(data.chat_message);

                }

            } catch (error) {

                console.error(error);

            }

        }

        function appendMessage(message)
        {

            const container =
                document.getElementById('messages-container');

            if (!container || !message || messageIds.has(message.id)) {
                return;
            }

            messageIds.add(message.id);

            container.innerHTML += `
                <div class="flex justify-end mb-6" data-message-id="${message.id}">

                    <div class="max-w-[75%] lg:max-w-[65%]">

                        <div class="px-5 py-3.5 rounded-[22px] shadow-sm bg-[#1877f2] text-white rounded-br-md">

                            <p class="text-sm leading-relaxed break-words">

                                ${escapeHtml(message.message)}

                            </p>

                            <div class="text-xs mt-2 text-blue-100">

                                ${formatTime(message.created_at)}

                            </div>

                        </div>

                    </div>

                </div>
            `;

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

        function escapeHtml(text)
        {

            const div = document.createElement('div');

            div.textContent = text;

            return div.innerHTML;

        }

        function formatTime(dateString)
        {

            const date = new Date(dateString);

            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

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

        function handleTicketMessageEvent(event)
        {
            if (!event?.ticket_id || !event?.message) {
                return;
            }

            const ticketId = event.ticket_id;
            const message = event.message;
            const ticketItem = document.querySelector(`.conversation-item[data-id="${ticketId}"]`);

            if (ticketItem) {
                updateSidebarConversation(ticketItem, message);
            }

            if (message.sender_type !== 'agent') {
                showTicketNotification(
                    `New message from ${ticketItem ? ticketItem.querySelector('h3')?.textContent.trim() : 'customer'}`,
                    message.message
                );
            }

            if (String(ticketId) === String(currentTicketId)) {
                appendMessage(message);
                clearConversationUnread(ticketItem);
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

        function updateSidebarConversation(item, message)
        {
            const snippet = item.querySelector('.conversation-snippet');
            const time = item.querySelector('.conversation-time');
            const badge = getOrCreateUnreadBadge(item);
            const unreadCount = parseInt(item.dataset.unreadCount || '0', 10);
            const nextCount = message.sender_type !== 'agent' ? unreadCount + 1 : unreadCount;

            if (snippet) {
                snippet.textContent = message.message;
            }

            if (time) {
                time.textContent = formatTime(message.created_at);
            }

            if (message.sender_type !== 'agent' && badge) {
                badge.dataset.count = nextCount;
                badge.textContent = nextCount > 99 ? '99+' : nextCount;
                if (nextCount > 0) {
                    badge.classList.remove('hidden');
                    badge.style.display = 'inline-flex';
                    item.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
                } else {
                    badge.classList.add('hidden');
                    badge.style.display = 'none';
                    item.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                }
                item.dataset.unreadCount = nextCount;
            }
        }

        function clearConversationUnread(item)
        {
            if (!item) {
                return;
            }

            const badge = item.querySelector('.conversation-unread-badge');

            if (badge) {
                badge.dataset.count = '0';
                badge.textContent = '';
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }

            item.dataset.unreadCount = '0';
            item.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
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
                    } else {
                        badge.classList.add('hidden');
                        badge.style.display = 'none';
                        ticketItem.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                    }
                }
            } catch (error) {
                console.error('Unable to mark ticket unread', error);
            }
        }

        function bindUnreadToggle()
        {
            const button = document.getElementById('toggle-unread-btn');
            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                if (!currentTicketId) {
                    return;
                }

                markTicketUnread(currentTicketId);
            });
        }

    </script>

</x-layouts.app>
