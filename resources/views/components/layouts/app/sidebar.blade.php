            <aside :class="{ 'w-full md:w-64': sidebarOpen, 'w-0 md:w-16 hidden md:block': !sidebarOpen }"
                class="bg-sidebar text-sidebar-foreground border-r border-gray-200 dark:border-gray-700 sidebar-transition overflow-hidden">
                <!-- Sidebar Content -->
                <div class="h-full flex flex-col">
                    <!-- Sidebar Menu -->
                    <nav class="flex-1 overflow-y-auto custom-scrollbar py-4">
                        <ul class="space-y-1 px-2">
                            <!-- Dashboard -->
                            <x-layouts.sidebar-link href="{{ route('dashboard') }}" icon='fas-house'
                                :active="request()->routeIs('dashboard*')">Dashboard</x-layouts.sidebar-link>

                            @if(auth()->check() && auth()->user()->hasRole('admin'))
                                <!-- Admin area visible only for admin users -->
                                <x-layouts.sidebar-two-level-link-parent title="User Management" icon="fas-users"
                                    :active="request()->routeIs('admin*')">
                                    <x-layouts.sidebar-two-level-link href="{{ route('admin.users.index') }}" icon='fas-user-friends'
                                        :active="request()->routeIs('admin.users*')">Users</x-layouts.sidebar-two-level-link>
                                    <x-layouts.sidebar-two-level-link href="{{ route('admin.roles') }}" icon='fas-user-tag'
                                        :active="request()->routeIs('admin.roles*')">Roles</x-layouts.sidebar-two-level-link>
                                    <x-layouts.sidebar-two-level-link href="{{ route('admin.permissions') }}" icon='fas-key'
                                        :active="request()->routeIs('admin.permissions*')">Permissions</x-layouts.sidebar-two-level-link>
                                </x-layouts.sidebar-two-level-link-parent>
                            @endif

                            @if(auth()->check() && auth()->user()->hasPermission('view-pages'))
                                <x-layouts.sidebar-link href="{{ route('pages') }}" icon='fas-file-alt'
                                    :active="request()->routeIs('pages*')">Pages</x-layouts.sidebar-link>
                            @endif

                            @if(auth()->check() && auth()->user()->hasPermission('pages-queue'))
                                <x-layouts.sidebar-link href="{{ route('support-queues.index') }}" icon='fas-tasks'
                                    :active="request()->routeIs('support-queues*')">Pages Queue</x-layouts.sidebar-link>
                            @endif

                            @if(auth()->check() && auth()->user()->hasPermission('support-ticket'))
                                 <x-layouts.sidebar-link href="{{ route('all-tickets') }}" icon='fas-ticket-alt'
                                    :active="request()->routeIs('all-tickets')">Tickets</x-layouts.sidebar-link>
                            @endif

                            @if(auth()->check() && auth()->user()->hasRole(['admin', 'manager']))
                                <x-layouts.sidebar-link href="{{ route('reports.agent-performance') }}" icon='fas-chart-line'
                                    :active="request()->routeIs('reports.agent-performance*')">Agent Report</x-layouts.sidebar-link>
                                <x-layouts.sidebar-link href="{{ route('reports.page-performance') }}" icon='fas-chart-pie'
                                    :active="request()->routeIs('reports.page-performance*')">Page Report</x-layouts.sidebar-link>
                            @endif

                            @if(auth()->check() && auth()->user()->hasRole('admin'))
                                <x-layouts.sidebar-link href="{{ route('rag.index') }}" icon='fas-book-open'
                                    :active="request()->routeIs('rag*')">Knowledge Base</x-layouts.sidebar-link>
                            @endif



                            @if(auth()->check() &&  auth()->user()->hasPermission('support-ticket'))
                                <x-layouts.sidebar-link
                                    href="{{ route('tickets.index') }}"
                                    icon='fas-comments'
                                    :active="request()->routeIs('tickets*')">

                                    <span class="flex items-center">
                                        Conversations
                                    </span>
                                </x-layouts.sidebar-link>
                            @endif

                            {{--

                            <!-- Example three level -->
                            <x-layouts.sidebar-two-level-link-parent title="Example three level" icon="fas-house"
                                :active="request()->routeIs('three-level*')">
                                <x-layouts.sidebar-two-level-link href="#" icon='fas-house'
                                    :active="request()->routeIs('three-level*')">Single Link</x-layouts.sidebar-two-level-link>

                                <x-layouts.sidebar-three-level-parent title="Third Level" icon="fas-house"
                                    :active="request()->routeIs('three-level*')">
                                    <x-layouts.sidebar-three-level-link href="#" :active="request()->routeIs('three-level*')">
                                        Third Level Link
                                    </x-layouts.sidebar-three-level-link>
                                </x-layouts.sidebar-three-level-parent>
                            </x-layouts.sidebar-two-level-link-parent> --}}
                        </ul>
                    </nav>

                    <div x-data="{ open: false, selectedAgent: null }" @click.away="open = false" class="relative border-t border-gray-200 dark:border-gray-700 p-3 flex-shrink-0">
                        <button @click="open = !open"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-500 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                            :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                            <span class="inline-flex items-center gap-2">
                                <i class="fas fa-user-friends"></i>
                                <span x-show="sidebarOpen" class="whitespace-nowrap">Agent status</span>
                            </span>
                            <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-700 dark:bg-blue-900 dark:text-blue-100">{{ $sidebarAgents->count() }}</span>
                        </button>

                        <div x-show="open" x-transition x-cloak class="absolute bottom-16 left-0 w-full z-20 overflow-hidden rounded-2xl border border-gray-200 bg-white p-3 text-sm text-gray-700 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            @php
                                $statusClasses = [
                                    'online' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                    'busy' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
                                    'away' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                                    'offline' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                                ];
                            @endphp

                            <div class="max-h-[360px] overflow-y-auto space-y-3">
                                @forelse($sidebarAgents as $agent)
                                    <div class="rounded-2xl border border-gray-100 px-3 py-3 dark:border-gray-800">
                                        <button type="button" @click="selectedAgent = selectedAgent === {{ $agent->id }} ? null : {{ $agent->id }}"
                                            class="flex w-full items-center justify-between text-left">
                                            <div>
                                                <div class="font-medium text-sm">{{ $agent->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Pending: {{ $agent->pending_tickets_count }}</div>
                                            </div>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$agent->availability_status ?? 'offline'] ?? $statusClasses['offline'] }}">
                                                {{ ucfirst($agent->availability_status ?? 'offline') }}
                                            </span>
                                        </button>

                                        <div x-show="selectedAgent === {{ $agent->id }}" x-transition x-cloak class="mt-3 space-y-2 rounded-2xl bg-gray-50 p-3 dark:bg-gray-800">
                                            @if($agent->tickets->isNotEmpty())
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pending tickets</div>
                                                <ul class="space-y-2">
                                                    @foreach($agent->tickets as $ticket)
                                                        <li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                                                            <div class="font-medium">#{{ $ticket->id }} - {{ \Illuminate\Support\Str::limit($ticket->subject, 32) }}</div>
                                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="text-xs text-gray-500 dark:text-gray-400">No pending tickets for this agent.</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500 dark:text-gray-400">No agents available.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
