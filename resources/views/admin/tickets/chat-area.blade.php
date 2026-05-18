<div class="flex-1 flex flex-col h-full bg-gray-50 dark:bg-gray-900 relative">

    @php
        $messages = $ticket->messages()->with('user')->orderBy('created_at','asc')->get();
        $fileAttachments = [];
        foreach ($messages as $message) {
            if (empty($message->attachments) || !is_array($message->attachments)) {
                continue;
            }
            foreach ($message->attachments as $attachment) {
                $url = $attachment['payload']['url'] ?? $attachment['url'] ?? null;
                if (!$url) {
                    continue;
                }
                $type = $attachment['type'] ?? 'file';
                $filename = basename(parse_url($url, PHP_URL_PATH) ?: $url);
                $fileAttachments[] = [
                    'url' => $url,
                    'type' => $type,
                    'filename' => $filename,
                    'sender' => $message->message_type === 'agent' ? ($message->user?->name ?: 'Agent') : ($message->message_type === 'customer' ? ($ticket->customer_name ?: 'Customer') : ucfirst($message->message_type)),
                    'created_at' => $message->created_at,
                ];
            }
        }
    @endphp

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
                $previousAgentId = null;
            @endphp

            @foreach($ticket->messages()->with('user')->orderBy('created_at','asc')->get() as $message)
                @php
                    $showMeta = false;
                    $showTime = false;
                    $currentAgentId = $message->message_type === 'agent' ? $message->user_id : null;

                    if (!$previousMessage) {
                        $showMeta = true;
                        $showTime = true;
                    } else {
                        $showMeta = $message->message_type !== $previousMessage->message_type ||
                            $message->created_at->diffInMinutes($previousMessage->created_at) > $messageGapThreshold ||
                            ($message->message_type === 'agent' && $currentAgentId !== $previousAgentId);
                        $showTime = $message->created_at->diffInMinutes($previousMessage->created_at) > 5;
                    }
                @endphp

                @if($message->message_type === 'agent')
                    <div class="flex justify-end" data-message-id="{{ $message->id }}" data-message-type="agent" data-created-at="{{ $message->created_at->toDateTimeString() }}">
                        <div class="flex flex-col gap-1 max-w-[70%]">
                            <div class="px-4 py-2.5 rounded-2xl rounded-br-md bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white shadow-sm">
                                <p class="text-sm leading-relaxed break-words">
                                    {{ $message->message }}
                                </p>
                                @if($message->attachments)
                                    @foreach($message->attachments as $attachment)
                                        @if($attachment['type'] === 'image')
                                            <img src="{{ $attachment['payload']['url'] }}" alt="Image" class="max-w-full rounded mt-2 cursor-pointer shadow-sm hover:opacity-90" style="max-height:240px;" onclick="showImagePreview('{{ $attachment['payload']['url'] }}')">
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
                            @if($showMeta)
                                <div class="text-xs flex justify-end items-center gap-2 mt-1">
                                    <span class="text-gray-400 dark:text-gray-500">{{ $message->user?->name ?? 'Agent' }}</span>
                                </div>
                            @endif
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
                                                <img src="{{ $attachment['payload']['url'] }}" alt="Image" class="max-w-full rounded mt-2 cursor-pointer shadow-sm hover:opacity-90" style="max-height:240px;" onclick="showImagePreview('{{ $attachment['payload']['url'] }}')">
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
                @php $previousAgentId = $message->message_type === 'agent' ? $message->user_id : $previousAgentId; @endphp
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

                    <button
                        type="button"
                        id="replay-suggest-btn"
                        onclick="getAISuggestions({{ $ticket->id }})"
                        class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900 hover:bg-purple-200 dark:hover:bg-purple-800 transition flex items-center justify-center text-purple-600 dark:text-purple-400"
                        title="Get AI suggestions"
                    >
                        🤖
                    </button>

                </div>

                {{-- INPUT --}}
                <div class="flex-1 flex flex-col">

                    <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Replying as <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->facebookPage?->page_name ?? 'Messenger' }}</span>
                    </div>

                    <div class="flex items-end gap-3">
                        <textarea
                            id="agent_message"
                            rows="1"
                            placeholder="Write a message... (Press Enter to send)"
                            class="flex-1 resize-none bg-gray-100 dark:bg-gray-700 border border-transparent focus:border-[#1877f2] dark:focus:border-blue-400 px-4 py-2.5 rounded-2xl text-sm outline-none leading-relaxed max-h-32 overflow-y-auto text-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition"
                        ></textarea>

                        <button
                            type="submit"
                            class="h-10 w-10 rounded-full bg-gradient-to-r from-[#1877f2] to-[#1b74e4] text-white flex items-center justify-center hover:scale-110 active:scale-95 transition shadow-md"
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

                    <div id="attachment-preview" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>

                    <!-- AI Suggestions Container -->
                    <div id="ai-suggestions" class="mt-2 hidden">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">AI Suggestions:</p>
                        <div id="suggestions-list" class="space-y-1"></div>
                    </div>
                </div>

            </form>

        </div>

    </div>

    {{-- FILES PANEL (shows in chat area) --}}
    <div id="files-panel" class="hidden flex-1 overflow-y-auto px-4 py-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Conversation Files</h3>
                <button type="button" onclick="closeFilesPanel()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-0 overflow-y-auto">
                @if(count($fileAttachments) > 0)
                    <div class="space-y-4">
                        @foreach($fileAttachments as $file)
                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        @if($file['type'] === 'image')
                                            <button type="button" onclick="showImagePreview('{{ $file['url'] }}')" class="h-16 w-16 overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900 shadow-sm">
                                                <img src="{{ $file['url'] }}" alt="Image preview" class="h-full w-full object-cover">
                                            </button>
                                        @else
                                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-gray-200 bg-gray-100 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                {{ strtoupper(substr($file['type'], 0, 3)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $file['filename'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $file['sender'] }} · {{ $file['created_at']->format('h:i A') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($file['type'] === 'image')
                                            <button type="button" onclick="showImagePreview('{{ $file['url'] }}')" class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">Preview</button>
                                        @else
                                            <a href="{{ $file['url'] }}" target="_blank" class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">Open file</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-4xl mb-4">📎</div>
                        <p class="text-gray-500 dark:text-gray-400">No files attached to this conversation yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- LOGS PANEL (shows in chat area) --}}
    <div id="logs-panel" class="hidden flex-1 overflow-y-auto px-4 py-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ticket Events</h3>
                <button type="button" onclick="closeLogsPanel()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-0 overflow-y-auto">
                @php $logs = $ticket->logs()->orderBy('created_at','desc')->get(); @endphp
                @if($logs->count() > 0)
                    <div class="space-y-3">
                        @foreach($logs as $log)
                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700">
                                            <span class="text-sm">{{ strtoupper(substr($log->action,0,1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-baseline gap-2">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $log->formatted_action }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('M d, Y H:i') }}</p>
                                        </div>
                                        @if($log->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $log->description }}</p>
                                        @endif
                                        @if($log->user)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">by {{ $log->user->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">No events logged for this ticket.</div>
                @endif
            </div>
        </div>
    </div>

</div>
