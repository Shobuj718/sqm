<x-layouts.app>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Manage User: ') . $user->name }}
            </h2>
            <a href="{{ route('admin.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- User Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-16 w-16">
                            <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                                <span class="text-xl font-medium text-gray-700">{{ $user->initials() }}</span>
                            </div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-2xl font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                            <p class="text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500">Joined {{ $user->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Roles Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Roles</h3>
                            <button onclick="showAssignRoleModal()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-1 px-3 rounded">
                                Assign Role
                            </button>
                        </div>

                        @if($user->roles->count() > 0)
                            <div class="space-y-3">
                                @foreach($user->roles as $role)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</h4>
                                        @if($role->description)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $role->description }}</p>
                                        @endif
                                    </div>
                                    <button onclick="removeRoleFromUser('{{ $role->name }}')" class="text-red-600 hover:text-red-900 text-sm">
                                        Remove
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">No roles assigned</p>
                        @endif
                    </div>
                </div>

                <!-- Direct Permissions Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Direct Permissions</h3>
                            <button onclick="showAssignPermissionModal()" class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-1 px-3 rounded">
                                Assign Permission
                            </button>
                        </div>

                        @if($user->permissions->count() > 0)
                            <div class="space-y-3">
                                @foreach($user->permissions as $permission)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $permission->name }}</h4>
                                        @if($permission->description)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $permission->description }}</p>
                                        @endif
                                    </div>
                                    <button onclick="removePermissionFromUser('{{ $permission->name }}')" class="text-red-600 hover:text-red-900 text-sm">
                                        Remove
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">No direct permissions assigned</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Effective Permissions Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Effective Permissions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">All permissions this user has (from roles + direct assignments)</p>

                    @php
                        $effectivePermissions = collect();
                        foreach($user->roles as $role) {
                            $effectivePermissions = $effectivePermissions->merge($role->permissions);
                        }
                        $effectivePermissions = $effectivePermissions->merge($user->permissions)->unique('id');
                    @endphp

                    @if($effectivePermissions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($effectivePermissions as $permission)
                                <div class="flex items-center p-2 bg-green-50 dark:bg-green-900 rounded">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ $permission->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">No effective permissions</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div id="assignRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Assign Role to {{ $user->name }}</h3>
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
                        <select name="role" id="role_select" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Choose a role...</option>
                            @foreach(\App\Models\Role::all() as $role)
                                <option value="{{ $role->name }}">{{ $role->name }} - {{ $role->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAssignRoleModal()" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Assign Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Permission Modal -->
    <div id="assignPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Assign Permission to {{ $user->name }}</h3>
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
                        <select name="permission" id="permission_select" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Choose a permission...</option>
                            @foreach(\App\Models\Permission::all() as $permission)
                                <option value="{{ $permission->name }}">{{ $permission->name }} - {{ $permission->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAssignPermissionModal()" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Assign Permission
                        </button>
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
