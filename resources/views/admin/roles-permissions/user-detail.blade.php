<x-layouts.app>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Manage User: ') . $user->name }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Adjust roles and permissions for this user in one place.</p>
            </div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back to Dashboard
            </a>
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 grid gap-6 md:grid-cols-[0.9fr_1.1fr] items-center">
                    <div>
                        <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                        <p class="text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Joined {{ $user->created_at->format('M j, Y') }}</p>
                    </div>
                    <div class="flex items-center gap-3 justify-start md:justify-end">
                        <div class="inline-flex items-center gap-3 rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            <span class="font-semibold">Roles:</span>
                            {{ $user->roles->count() }}
                        </div>
                        <div class="inline-flex items-center gap-3 rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            <span class="font-semibold">Permissions:</span>
                            {{ $user->permissions->count() }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="border-b border-gray-200 dark:border-gray-700 lg:border-b-0 lg:border-r lg:border-gray-200 dark:lg:border-gray-700">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assigned Roles</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Roles grant permission groups to the user.</p>
                                </div>
                                <button onclick="showAssignRoleModal()" class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Assign Role</button>
                            </div>

                            @if($user->roles->count())
                                <div class="space-y-3">
                                    @foreach($user->roles as $role)
                                        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $role->name }}</h4>
                                                    @if($role->description)
                                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $role->description }}</p>
                                                    @endif
                                                </div>
                                                <button onclick="removeRoleFromUser('{{ $role->name }}')" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">Remove</button>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($role->permissions as $permission)
                                                    <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-200">{{ $permission->name }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No roles assigned yet.</p>
                            @endif
                        </div>
                    </div>

                    <div class="">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Direct Permissions</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Use direct permissions for exceptions or overrides.</p>
                                </div>
                                <button onclick="showAssignPermissionModal()" class="inline-flex items-center px-3 py-2 rounded-md bg-green-600 text-white text-sm font-medium hover:bg-green-700">Assign Permission</button>
                            </div>

                            @if($user->permissions->count())
                                <div class="space-y-3">
                                    @foreach($user->permissions as $permission)
                                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $permission->name }}</h4>
                                                @if($permission->description)
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $permission->description }}</p>
                                                @endif
                                            </div>
                                            <button onclick="removePermissionFromUser('{{ $permission->name }}')" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">Remove</button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No direct permissions assigned yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Effective Permissions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Permissions from both roles and direct assignment.</p>

                    @php
                        $effectivePermissions = collect();
                        foreach ($user->roles as $role) {
                            $effectivePermissions = $effectivePermissions->merge($role->permissions);
                        }
                        $effectivePermissions = $effectivePermissions->merge($user->permissions)->unique('id');
                    @endphp

                    @if($effectivePermissions->count())
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($effectivePermissions as $permission)
                                <div class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $permission->name }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No effective permissions.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="assignRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Role to {{ $user->name }}</h3>
                    <button onclick="closeAssignRoleModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.user.assign-role', $user) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="role_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Role</label>
                        <select name="role" id="role_select" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                            <option value="">Choose a role...</option>
                            @foreach(\App\Models\Role::all() as $role)
                                <option value="{{ $role->name }}">{{ $role->name }} - {{ $role->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeAssignRoleModal()" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-sm font-medium text-white hover:bg-blue-700">Assign Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="assignPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Permission to {{ $user->name }}</h3>
                    <button onclick="closeAssignPermissionModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.user.assign-permission', $user) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="permission_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Permission</label>
                        <select name="permission" id="permission_select" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                            <option value="">Choose a permission...</option>
                            @foreach(\App\Models\Permission::all() as $permission)
                                <option value="{{ $permission->name }}">{{ $permission->name }} - {{ $permission->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeAssignPermissionModal()" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-green-600 text-sm font-medium text-white hover:bg-green-700">Assign Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAssignRoleModal() {
            document.getElementById('assignRoleModal').classList.remove('hidden');
        }

        function closeAssignRoleModal() {
            document.getElementById('assignRoleModal').classList.add('hidden');
        }

        function showAssignPermissionModal() {
            document.getElementById('assignPermissionModal').classList.remove('hidden');
        }

        function closeAssignPermissionModal() {
            document.getElementById('assignPermissionModal').classList.add('hidden');
        }

        function removeRoleFromUser(roleName) {
            if (confirm('Are you sure you want to remove this role from the user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.user.remove-role", $user) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfToken);

                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'role';
                roleInput.value = roleName;
                form.appendChild(roleInput);

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function removePermissionFromUser(permissionName) {
            if (confirm('Are you sure you want to remove this permission from the user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.user.remove-permission", $user) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfToken);

                const permissionInput = document.createElement('input');
                permissionInput.type = 'hidden';
                permissionInput.name = 'permission';
                permissionInput.value = permissionName;
                form.appendChild(permissionInput);

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-layouts.app>
