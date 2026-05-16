<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Page Performance
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Facebook page wise ticket, message, agent, and reply-time report.
                </p>
            </div>
            <a href="{{ route('reports.agent-performance') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium text-sm dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <i class="fas fa-user-clock"></i>
                Agent Report
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form method="GET" action="{{ route('reports.page-performance') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="page_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Facebook Page</label>
                    <select id="page_id" name="page_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200">
                        <option value="">All pages</option>
                        @foreach($pageOptions as $page)
                            <option value="{{ $page->id }}" {{ (string) $filters['page_id'] === (string) $page->id ? 'selected' : '' }}>
                                {{ $page->page_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From</label>
                    <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label for="to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To</label>
                    <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium text-sm">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('reports.page-performance') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium text-sm dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <i class="fas fa-rotate-left"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Pages</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['pages_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Tickets</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['tickets_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Customer Messages</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['customer_messages_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Agent Replies</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['agent_replies_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Connected Agents</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['connected_agents_count'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Avg Reply Time</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $summary['average_reply_time_human'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Page</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tickets</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Messages</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Agents</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Queues</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avg Reply</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fastest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Longest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Reply</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $report['page']['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report['page']['category'] ?? $report['page']['page_id'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <div class="font-semibold">{{ $report['tickets_count'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $report['open_tickets_count'] }} open, {{ $report['waiting_tickets_count'] }} waiting, {{ $report['solved_tickets_count'] }} solved
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $report['messenger_tickets_count'] }} messenger, {{ $report['comment_tickets_count'] }} comment
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <div>{{ $report['customer_messages_count'] }} customer</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report['agent_replies_count'] }} replies</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <div>{{ $report['connected_agents_count'] }} connected</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report['assigned_agents_count'] }} assigned</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    @forelse($report['support_queues'] as $queue)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-100 mb-1">{{ $queue['name'] }}</span>
                                    @empty
                                        <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                    @endforelse
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $report['response_times']['average_human'] }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $report['response_times']['samples_count'] }} samples</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $report['response_times']['fastest_human'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $report['response_times']['longest_human'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $report['latest_reply_at'] ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No page performance data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
