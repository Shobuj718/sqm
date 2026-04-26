<!-- Header -->
<header class="bg-white dark:bg-gray-800 shadow-sm z-20 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between h-16 px-4">
        <!-- Left side: Logo and toggle -->
        <div class="flex items-center">
            <button @click="toggleSidebar"
                class="p-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="ml-4 font-semibold text-xl text-blue-600 dark:text-blue-400">{{ config('app.name') }}</div>
        </div>

        <!-- Right side: Theme toggle, Search, notifications, profile -->
        <div class="flex items-center space-x-4">
            <!-- Notifications Bell -->
            <div x-data="{ open: false, notificationCount: 0 }" class="relative">
                <button @click="open = !open" class="relative p-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none transition-colors duration-200">
                    <!-- Bell Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <!-- Badge with count -->
                    <template x-if="notificationCount > 0">
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                            <span x-text="notificationCount > 99 ? '99+' : notificationCount"></span>
                        </span>
                    </template>
                </button>

                <!-- Notification Dropdown -->
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg py-2 z-50 border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">New Messages</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`You have ${notificationCount} new message${notificationCount !== 1 ? 's' : ''}`"></p>
                    </div>

                    <template x-if="notificationCount === 0">
                        <div class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            No new messages
                        </div>
                    </template>

                    <div class="px-4 py-2">
                        <a href="{{ route('tickets.index') }}" class="block w-full text-center px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition-colors">
                            View All Tickets
                        </a>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const notificationBell = document.querySelector('[x-data*="notificationCount"]');
                        if (notificationBell) {
                            // Initial fetch
                            //fetchUnreadCount();
                            // Refresh every 5 seconds
                            //setInterval(fetchUnreadCount, 5000);
                        }
                    });

                    async function fetchUnreadCount() {
                        try {
                            const response = await fetch('/api/unread-messages-count');
                            const data = await response.json();
                            console.log('Unread messages count:', data.count);

                            // Update Alpine.js data
                            const bellElement = document.querySelector('[x-data*="notificationCount"]');
                            if (bellElement && bellElement.__x) {
                                bellElement.__x.$data.notificationCount = data.count;
                            }
                        } catch (error) {
                            console.error('Error fetching unread count:', error);
                        }
                    }
                </script>
            </div>

            <!-- Theme Toggle -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none transition-colors duration-200">
                    <!-- Sun icon for light mode -->
                    <svg x-show="localStorage.theme !== 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14 7 7 0 000-14z" />
                    </svg>
                    <!-- Moon icon for dark mode -->
                    <svg x-show="localStorage.theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute border border-gray-200 dark:border-gray-700 right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50">
                    <form id="header-appearance-form" action="{{ route('settings.appearance.update') }}" method="POST"
                        class="hidden">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="theme_preference" id="header_theme_preference">
                    </form>

                    <button type="button" onclick="persistTheme('light')"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center {{ (auth()->user()->theme_preference ?? 'system') === 'light' ? 'bg-gray-100 text-blue-700 dark:bg-gray-700 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14 7 7 0 000-14z" />
                        </svg>
                        Light
                    </button>
                    <button type="button" onclick="persistTheme('dark')"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center {{ (auth()->user()->theme_preference ?? 'system') === 'dark' ? 'bg-gray-100 text-blue-700 dark:bg-gray-700 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        Dark
                    </button>
                    <button type="button" onclick="persistTheme('system')"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center {{ (auth()->user()->theme_preference ?? 'system') === 'system' ? 'bg-gray-100 text-blue-700 dark:bg-gray-700 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        System
                    </button>
                </div>

                <script>
                    window.persistTheme = function(theme) {
                        // Update UI immediately (client-side)
                        if (typeof window.setAppearance === 'function') {
                            window.setAppearance(theme);
                        }

                        // Set and submit form for persistence
                        const form = document.getElementById('header-appearance-form');
                        const input = document.getElementById('header_theme_preference');
                        if (form && input) {
                            input.value = theme;
                            form.submit();
                        }
                    }
                </script>
            </div>
            <!-- Profile -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center focus:outline-none">
                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                        <span
                            class="flex h-full w-full items-center justify-center rounded-lg bg-gray-200 text-black dark:bg-gray-700 dark:text-white">
                            {{ Auth::user()->initials() }}
                        </span>
                    </span>
                    <span class="ml-2 hidden md:block">{{ Auth::user()->name }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" :class="{ 'block': open, 'hidden': !open }"
                    class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                    <a href="{{ route('settings.profile.edit') }}"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </div>
                    </a>
                    <div class="border-t border-gray-200 dark:border-gray-700"></div>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="block w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
