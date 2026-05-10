<div class="flex-1 flex flex-col h-full bg-gray-50 dark:bg-gray-900 relative">

    {{-- CHAT HEADER --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-4 py-3 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">

                {{-- PROFILE --}}
                <div class="relative shrink-0">

                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center font-bold text-sm shadow-sm">

                        {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}

                    </div>

                    {{-- ONLINE --}}
                    <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-white shadow-sm"></div>

                </div>

                {{-- INFO --}}
                <div>

                    <div class="flex items-center gap-2">

                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100 text-sm leading-tight">

                                {{ $ticket->customer_name ?? $ticket->customer_facebook_id }}

                            </h3>

                            @if($ticket->facebookPage)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <a href="https://www.facebook.com/{{ $ticket->facebookPage->page_id }}" target="_blank" class="hover:text-blue-500 transition">
                                        📘 {{ $ticket->facebookPage->page_name }}
                                    </a>
                                </p>
                            @endif

                            @if($ticket->customer_facebook_id)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <a href="https://www.facebook.com/profile.php?id={{ $ticket->customer_facebook_id }}" target="_blank" rel="noopener noreferrer" class="hover:text-blue-500 transition">
                                        🔗 View customer profile
                                    </a>
                                </p>
                            @endif
                        </div>

                        <span class="px-2 py-1 rounded-full bg-[#e7f3ff] dark:bg-blue-900/30 text-[#1877f2] dark:text-blue-300 text-[10px] font-semibold ml-2">

                            Messenger

                        </span>

                    </div>

                </div>

            </div>

            {{-- CONTROLS --}}
            <div class="flex items-center gap-2">
                <select id="ticket-status" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400 transition">
                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>

                <select id="ticket-priority" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400 transition">
                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>

                <select id="ticket-agent" class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-xs text-gray-700 dark:text-gray-200 outline-none focus:border-[#1877f2] dark:focus:border-blue-400 transition">
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
                    class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                >
                    Mark unread
                </button>
            </div>
        </div>
    </div>

    {{-- MESSAGE AREA --}}
    <div
        id="messages-container"
        class="flex-1 overflow-y-auto px-4 py-6"
        style="scroll-behavior:smooth"
    >

        <div class="max-w-2xl mx-auto space-y-4">

            @php
                $messageGapThreshold = 30; // minutes
                $previousMessage = null;
            @endphp

            @foreach($ticket->messages()->orderBy('created_at','asc')->get() as $message)
                @php
                    $showMeta = false;
                    $showTime = false;

                    if (!$previousMessage) {
                        $showMeta = true;
                        $showTime = true;
                    } else {
                        $showMeta = $message->message_type !== $previousMessage->message_type ||
                            $message->created_at->diffInMinutes($previousMessage->created_at) > $messageGapThreshold;
                        $showTime = $message->created_at->diffInMinutes($previousMessage->created_at) > 5;
                    }
                @endphp

                @if($message->message_type === 'agent')
                    <div class="flex justify-end" data-message-id="{{ $message->id }}" data-message-type="agent" data-created-at="{{ $message->created_at->toDateTimeString() }}">
                        <div class="flex flex-col gap-1 max-w-[70%]">
                            @if($showMeta)
                                <div class="text-xs font-semibold mb-1.5 opacity-90 flex justify-end items-center gap-2 text-white">
                                    <span class="rounded-full bg-white/10 px-2 py-1 text-white">
                                        Replying as {{ $ticket->facebookPage?->page_name ?? 'Agent' }}
                                    </span>
                                </div>
                            @endif
                            <div class="px-4 py-2.5 rounded-2xl rounded-br-md bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white shadow-sm">
                                <p class="text-sm leading-relaxed break-words">
                                    {{ $message->message }}
                                </p>
                                @if($message->attachments)
                                    @foreach($message->attachments as $attachment)
                                        @if($attachment['type'] === 'image')
                                            <img src="{{ $attachment['payload']['url'] }}" alt="Image" class="max-w-full rounded mt-2">
                                        @elseif($attachment['type'] === 'video')
                                            <video controls class="max-w-full rounded mt-2">
                                                <source src="{{ $attachment['payload']['url'] }}" type="video/mp4">
                                            </video>
                                        @elseif($attachment['type'] === 'audio')
                                            <audio controls class="mt-2">
                                                <source src="{{ $attachment['payload']['url'] }}" type="audio/mpeg">
                                            </audio>
                                        @elseif($attachment['type'] === 'file' && str_contains($attachment['payload']['url'], '.pdf'))
                                            <a href="{{ $attachment['payload']['url'] }}" target="_blank" class="text-blue-200 underline mt-2 block">Download PDF</a>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                            @if($showTime)
                                <span class="text-xs text-gray-200 text-right px-2">
                                    {{ $message->created_at->format('h:i A') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex justify-start" data-message-id="{{ $message->id }}" data-message-type="customer" data-created-at="{{ $message->created_at->toDateTimeString() }}">
                        <div class="flex items-end gap-2 max-w-[70%]">
                            @if($showMeta)
                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-[#1877f2] to-[#42a5f5] text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                    {{ strtoupper(substr($ticket->customer_name ?? 'U',0,1)) }}
                                </div>
                            @else
                                <div class="w-8"></div>
                            @endif
                            <div class="flex flex-col gap-1">
                                @if($showMeta)
                                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        {{ $ticket->customer_name ?? $ticket->customer_facebook_id }}
                                    </div>
                                @endif
                                <div class="px-4 py-2.5 rounded-2xl rounded-bl-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 shadow-sm border border-gray-200 dark:border-gray-600">
                                    <p class="text-sm leading-relaxed break-words">
                                        {{ $message->message }}
                                    </p>
                                    @if($message->attachments)
                                        @foreach($message->attachments as $attachment)
                                            @if($attachment['type'] === 'image')
                                                <img src="{{ $attachment['payload']['url'] }}" alt="Image" class="max-w-full rounded mt-2">
                                            @elseif($attachment['type'] === 'video')
                                                <video controls class="max-w-full rounded mt-2">
                                                    <source src="{{ $attachment['payload']['url'] }}" type="video/mp4">
                                                </video>
                                            @elseif($attachment['type'] === 'audio')
                                                <audio controls class="mt-2">
                                                    <source src="{{ $attachment['payload']['url'] }}" type="audio/mpeg">
                                                </audio>
                                            @elseif($attachment['type'] === 'file' && str_contains($attachment['payload']['url'], '.pdf'))
                                                <a href="{{ $attachment['payload']['url'] }}" target="_blank" class="text-blue-600 underline mt-2 block">Download PDF</a>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                @if($showTime)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 px-2">
                                        {{ $message->created_at->format('h:i A') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @php $previousMessage = $message; @endphp
            @endforeach

        </div>

    </div>

    {{-- CHAT INPUT --}}
    <div class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 px-4 py-4 shrink-0 shadow-[0_-2px_8px_rgba(0,0,0,0.05)]">

        <div class="max-w-2xl mx-auto">

            <form
                id="reply-form"
                class="flex items-end gap-3"
            >

                @csrf

                {{-- LEFT ACTIONS --}}
                <div class="flex items-center gap-2 pb-2">

                    <button
                        type="button"
                        id="attach-file-btn"
                        class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-400"
                        title="Attach file"
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
                    <input type="file" id="reply-attachments" name="attachments[]" class="hidden" multiple>

                    <button
                        type="button"
                        class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-400 text-lg"
                        title="Add emoji"
                    >

                        😊

                    </button>

                </div>

                {{-- INPUT --}}
                <div class="flex-1 relative">

                    <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Replying as <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->facebookPage?->page_name ?? 'Messenger' }}</span>
                    </div>

                    <textarea
                        id="agent_message"
                        rows="1"
                        placeholder="Write a message... (Press Enter to send)"
                        class="w-full resize-none bg-gray-100 dark:bg-gray-700 border border-transparent focus:border-[#1877f2] dark:focus:border-blue-400 px-4 py-2.5 rounded-2xl text-sm outline-none leading-relaxed max-h-32 overflow-y-auto text-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition"
                    ></textarea>

                    <div id="attachment-preview" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>

                    {{-- SEND --}}
                    <button
                        type="submit"
                        class="absolute right-3 bottom-2.5 h-8 w-8 rounded-full bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white flex items-center justify-center hover:scale-110 active:scale-95 transition shadow-md"
                        title="Send message"
                    >

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-4 w-4 rotate-45"
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
