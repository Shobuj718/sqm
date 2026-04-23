<x-layouts.app>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Permissions Management') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create permissions and see which roles currently use them.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Back to Dashboard
                </a>
                <button onclick="showCreatePermissionModal()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Create Permission
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700 dark:border-green-700 dark:bg-green-950/40">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-700 dark:bg-red-950/40">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid gap-6 mb-8 lg:grid-cols-[1.4fr_0.6fr]">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Permission List</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Permissions can be assigned to roles, and users inherit them through those roles.</p>

                        <div class="mt-6">
                            <label for="permissionSearch" class="sr-only">Search permissions</label>
                            <input id="permissionSearch" type="search" oninput="filterPermissions()" placeholder="Search permissions..." class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-4 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">How permissions work</h3>
                        <ul class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                Assign permissions to roles, and roles to users.
                            </li>
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-green-600"></span>
                                Use direct permissions only when needed for exceptions.
                            </li>
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
                                This page gives visibility into current permission usage.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="permissionsTable">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Permission</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Roles</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($permissions as $permission)
                                <tr class="permission-row hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $permission->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $permission->description ?? 'No description' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if($permission->roles->count())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($permission->roles as $role)
                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">None</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $permission->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No permissions created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="createPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create New Permission</h3>
                    <button onclick="closeCreatePermissionModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.permissions.create') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="permission_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permission Name</label>
                        <input type="text" name="name" id="permission_name" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                    </div>
                    <div class="mb-4">
                        <label for="permission_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="permission_description" rows="4" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeCreatePermissionModal()" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-green-600 text-sm font-medium text-white hover:bg-green-700">Create Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showCreatePermissionModal() {
            document.getElementById('createPermissionModal').classList.remove('hidden');
        }

        function closeCreatePermissionModal() {
            document.getElementById('createPermissionModal').classList.add('hidden');
        }

        function filterPermissions() {
            const filter = document.getElementById('permissionSearch').value.toLowerCase();
            document.querySelectorAll('#permissionsTable .permission-row').forEach(row => {
                const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const description = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const roles = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                row.style.display = name.includes(filter) || description.includes(filter) || roles.includes(filter) ? '' : 'none';
            });
        }
    </script>
</x-layouts.app>
