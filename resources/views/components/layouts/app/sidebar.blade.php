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

                            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager')))
                                <x-layouts.sidebar-link href="{{ route('tickets.index') }}" icon='fas-ticket-alt'
                                    :active="request()->routeIs('tickets*')">
                                    <span class="flex items-center">
                                        Support Tickets
                                        @php
                                            $openCount = \App\Models\Ticket::where('status', 'open')->orWhere('status', 'in_progress')->count();
                                        @endphp
                                        @if($openCount > 0)
                                            <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 bg-red-600 rounded-full">{{ $openCount }}</span>
                                        @endif
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
                </div>
            </aside>
