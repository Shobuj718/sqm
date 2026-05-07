<div class="flex-1 flex flex-col h-full bg-gray-50 dark:bg-gray-900 relative">

    {{-- CHAT HEADER --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-3 py-2 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">

                {{-- PROFILE --}}
                <div class="relative shrink-0">

                    <div class="h-8 w-8 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center font-bold text-sm shadow-sm">

                        {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}

                    </div>

                    {{-- ONLINE --}}
                    <div class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-green-500 border-2 border-white shadow-sm"></div>

                </div>

                {{-- INFO --}}
                <div>

                    <div class="flex items-center gap-2">

                        <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm leading-none">

                            {{ $ticket->customer_name ?? $ticket->customer_facebook_id }}

                        </h3>

                        <span class="px-1.5 py-0.5 rounded-full bg-[#e7f3ff] dark:bg-blue-900/30 text-[#1877f2] dark:text-blue-300 text-[9px] font-semibold">

                            Messenger

                        </span>

                    </div>

                </div>

            </div>

            {{-- CONTROLS --}}
            <div class="flex items-center gap-2">
                <select id="ticket-status" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400">
                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>

                <select id="ticket-priority" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400">
                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>

                <select id="ticket-agent" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400">
                    <option value="">Unassigned</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $ticket->assigned_to === $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>

                <button
                    id="toggle-unread-btn"
                    type="button"
                    class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                >
                    Mark unread
                </button>
            </div>
        </div>
    </div>

    {{-- MESSAGE AREA --}}
    <div
        id="messages-container"
        class="flex-1 overflow-y-auto px-8 py-6"
        style="scroll-behavior:smooth"
    >

        <div class="max-w-5xl mx-auto">

            @foreach($ticket->messages()->orderBy('created_at','asc')->get() as $message)

                <div class="flex mb-6 {{ $message->message_type === 'agent' ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">

                    <div class="max-w-[75%] lg:max-w-[65%]">

                        {{-- CUSTOMER MESSAGE --}}
                        @if($message->message_type !== 'agent')

                            <div class="flex items-end gap-3">

                                {{-- AVATAR --}}
                                <div class="h-9 w-9 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center text-sm font-bold shrink-0 shadow-sm">

                                    {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}

                                </div>

                                <div>

                                    <div class="px-5 py-3.5 rounded-[24px] rounded-bl-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 shadow-sm border border-gray-100 dark:border-gray-600">

                                        <p class="text-[15px] leading-relaxed break-words">

                                            {{ $message->message }}

                                        </p>

                                    </div>

                                    <div class="text-xs mt-2 text-gray-400 dark:text-gray-500 px-1">

                                        {{ $message->created_at->format('h:i A') }}

                                    </div>

                                </div>

                            </div>

                        @else

                            {{-- AGENT MESSAGE --}}
                            <div>

                                <div class="px-5 py-3.5 rounded-[24px] rounded-br-md bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white shadow-sm">

                                    <p class="text-[15px] leading-relaxed break-words">

                                        {{ $message->message }}

                                    </p>

                                </div>

                                <div class="text-xs mt-2 text-right text-gray-400 dark:text-gray-500 px-1">

                                    {{ $message->created_at->format('h:i A') }}

                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            @endforeach

        </div>

    </div>

    {{-- CHAT INPUT --}}
    <div class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 px-6 py-4 shrink-0 shadow-[0_-1px_4px_rgba(0,0,0,0.03)]">

        <div class="max-w-5xl mx-auto">

            <form
                id="reply-form"
                class="flex items-end gap-3"
            >

                @csrf

                {{-- LEFT ACTIONS --}}
                <div class="flex items-center gap-2 pb-1">

                    <button
                        type="button"
                        class="h-11 w-11 rounded-2xl bg-[#f5f7fb] dark:bg-gray-600 hover:bg-[#e9edf5] dark:hover:bg-gray-500 transition flex items-center justify-center text-gray-500 dark:text-gray-400"
                    >

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 10.828a4 4 0 10-5.656-5.656L5.757 11.757a6 6 0 108.486 8.486L20.828 13"/>

                        </svg>

                    </button>

                    <button
                        type="button"
                        class="h-11 w-11 rounded-2xl bg-[#f5f7fb] dark:bg-gray-600 hover:bg-[#e9edf5] dark:hover:bg-gray-500 transition flex items-center justify-center text-gray-500 dark:text-gray-400"
                    >

                        😊

                    </button>

                </div>

                {{-- INPUT --}}
                <div class="flex-1 relative">

                    <textarea
                        id="agent_message"
                        rows="1"
                        placeholder="Write a message..."
                        class="w-full resize-none bg-[#f5f7fb] dark:bg-gray-600 border border-transparent focus:border-[#1877f2] dark:focus:border-blue-400 px-5 py-3.5 rounded-[24px] text-[15px] outline-none pr-16 leading-relaxed max-h-40 overflow-y-auto text-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                    ></textarea>

                    {{-- SEND --}}
                    <button
                        type="submit"
                        class="absolute right-2 bottom-2 h-10 w-10 rounded-2xl bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white flex items-center justify-center hover:scale-105 transition shadow-sm"
                    >

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5 rotate-45"
                             fill="currentColor"
                             viewBox="0 0 20 20">

                            <path d="M2.94 2.94a1.5 1.5 0 011.64-.33l12 5a1.5 1.5 0 010 2.78l-12 5a1.5 1.5 0 01-2.1-1.73l1.42-4.26a.5.5 0 000-.32L2.48 4.67a1.5 1.5 0 01.46-1.73z"/>

                        </svg>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
