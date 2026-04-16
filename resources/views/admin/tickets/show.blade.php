<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Support Ticket #' . $ticket->id) }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Conversation History -->
                <div class="lg:col-span-2">
                    <!-- Ticket Info Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->subject }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Customer: {{ $ticket->customer_name }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $ticket->status === 'open' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ($ticket->status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($ticket->status === 'resolved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : ($ticket->priority === 'medium' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200')) }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Created:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ $ticket->created_at->format('M d, Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Last Updated:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ $ticket->updated_at->format('M d, Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Channel:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">
                                        @if($ticket->messages->first()?->channel)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $ticket->messages->first()->channel === 'messenger' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                                {{ ucfirst($ticket->messages->first()->channel) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Total Messages:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ $ticket->messages->count() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Messages Container -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Conversation History</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-96 overflow-y-auto">
                            @forelse($ticket->messages()->orderBy('created_at', 'asc')->get() as $message)
                            <div class="flex {{ $message->message_type === 'customer' ? 'justify-start' : ($message->message_type === 'agent' ? 'justify-end' : 'justify-center') }}">
                                <div class="max-w-xs lg:max-w-md {{ $message->message_type === 'customer' ? 'bg-gray-100 dark:bg-gray-700' : ($message->message_type === 'agent' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-yellow-100 dark:bg-yellow-900') }} rounded-lg px-4 py-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $message->message_type === 'customer' ? '👤 Customer' : ($message->message_type === 'agent' ? '👨‍💼 Agent' : '⚙️ System') }}
                                        @if($message->channel)
                                        • {{ ucfirst($message->channel) }}
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ $message->message }}</p>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $message->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                No messages yet.
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reply</h3>
                        </div>
                        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="px-6 py-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="agent_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your Message</label>
                                <textarea
                                    id="agent_message"
                                    name="agent_message"
                                    rows="4"
                                    placeholder="Type your reply here..."
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200"
                                ></textarea>
                                @error('agent_message')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium text-sm">Send Reply</button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div class="lg:col-span-1">
                    <!-- Assignment Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Ticket</h3>
                        </div>
                        <div class="px-6 py-4">
                            <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="space-y-3">
                                @csrf
                                <select
                                    name="assigned_to"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    <option value="">Unassigned</option>
                                    @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ $ticket->assigned_to === $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium text-sm">Assign</button>
                            </form>
                        </div>
                    </div>

                    <!-- Status Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Change Status</h3>
                        </div>
                        <div class="px-6 py-4 space-y-2">
                            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <select
                                    name="status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium text-sm">Update Status</button>
                            </form>
                        </div>
                    </div>

                    <!-- Priority Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Change Priority</h3>
                        </div>
                        <div class="px-6 py-4">
                            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <select
                                    name="priority"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium text-sm">Update Priority</button>
                            </form>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Actions</h3>
                        </div>
                        <div class="px-6 py-4 space-y-2">
                            @if($ticket->status !== 'resolved')
                            <form method="POST" action="{{ route('tickets.resolve', $ticket) }}">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium text-sm">Mark as Resolved</button>
                            </form>
                            @endif

                            @if($ticket->status !== 'closed')
                            <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium text-sm">Close Ticket</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium text-sm">Delete</button>
                            </form>

                            <a href="{{ route('tickets.index') }}" class="block px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium text-sm text-center">Back to Tickets</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
