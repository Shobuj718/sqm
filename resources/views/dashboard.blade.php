<x-layouts.app>
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">SQM Operations Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Live overview of Facebook pages, conversations, support workload, and RAG knowledge.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('tickets.index') }}" class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Open Inbox</a>
            @if(auth()->check() && auth()->user()->hasRole('admin'))
                <a href="{{ route('rag.index') }}" class="rounded-md border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Knowledge Base</a>
            @endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Connected Pages</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($pagesCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $supportQueuesCount }} support queues configured</p>
                </div>
                <div class="rounded-md bg-blue-100 p-3 text-blue-600 dark:bg-blue-900 dark:text-blue-200">
                    <i class="fab fa-facebook-f"></i>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Conversations</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($activeTicketsCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $openTicketsCount }} open, {{ $waitingTicketsCount }} waiting</p>
                </div>
                <div class="rounded-md bg-emerald-100 p-3 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-200">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unread Customer Messages</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($unreadCustomerMessagesCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $todayMessagesCount }} total messages today</p>
                </div>
                <div class="rounded-md bg-amber-100 p-3 text-amber-600 dark:bg-amber-900 dark:text-amber-200">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">RAG Knowledge</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($ragSummary['embedded_documents_count']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $ragSummary['chunks_count'] }} embedded chunks</p>
                </div>
                <div class="rounded-md bg-violet-100 p-3 text-violet-600 dark:bg-violet-900 dark:text-violet-200">
                    <i class="fas fa-book-open"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.8fr)]">
        <section class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Ticket Health</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-4">
                @foreach($statusCounts as $status => $count)
                    @php
                        $colors = [
                            'open' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100',
                            'waiting' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-100',
                            'solved' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100',
                            'closed' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-100',
                        ];
                    @endphp
                    <div class="rounded-md border border-gray-100 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium capitalize text-gray-600 dark:text-gray-300">{{ $status }}</span>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $colors[$status] }}">{{ number_format($count) }}</span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-blue-500" style="width: {{ $ticketsCount > 0 ? min(100, round(($count / $ticketsCount) * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-gray-200 px-5 py-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                {{ number_format($ticketsCount) }} total tickets. {{ number_format($todayTicketsCount) }} new today.
                Messenger: {{ number_format($messengerTicketsCount) }}, Comments: {{ number_format($commentTicketsCount) }}.
            </div>
        </section>

        <section class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">7-Day Ticket Trend</h2>
            </div>
            <div class="space-y-3 p-5">
                @php $maxTrend = max(1, $lastSevenDayTrend->max('total')); @endphp
                @foreach($lastSevenDayTrend as $day)
                    <div class="grid grid-cols-[72px_minmax(0,1fr)_40px] items-center gap-3 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ $day['label'] }}</span>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ round(($day['total'] / $maxTrend) * 100) }}%"></div>
                        </div>
                        <span class="text-right font-medium text-gray-800 dark:text-gray-100">{{ $day['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recent Conversations</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Page</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Agent</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentTickets as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="px-5 py-4">
                                    <a href="{{ route('tickets.show', $ticket['id']) }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">#{{ $ticket['id'] }} {{ \Illuminate\Support\Str::limit($ticket['customer_name'], 24) }}</a>
                                    <div class="mt-1 max-w-sm truncate text-xs text-gray-500 dark:text-gray-400">{{ $ticket['latest_message'] }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $ticket['page_name'] }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-100">{{ $ticket['status'] }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $ticket['agent_name'] ?? 'Unassigned' }}</td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">{{ $ticket['updated_at']?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">No conversations yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Top Pages</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topPages as $page)
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $page['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $page['category'] ?? 'Facebook page' }}</div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $page['tickets_count'] }}</span>
                            </div>
                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $page['active_count'] }} active, {{ $page['comment_count'] }} comments, {{ $page['messenger_count'] }} messenger</div>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-gray-500 dark:text-gray-400">No connected pages yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Agent Workload</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($agentWorkload as $agent)
                        <div class="flex items-center justify-between gap-3 p-5">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $agent->name }}</div>
                                <div class="text-xs capitalize text-gray-500 dark:text-gray-400">{{ $agent->availability_status ?? 'offline' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $agent->active_tickets_count }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">active</div>
                            </div>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-gray-500 dark:text-gray-400">No agent workload yet.</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-200 px-5 py-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                    {{ $availableAgentsCount }} online out of {{ $agentsCount }} support users.
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
