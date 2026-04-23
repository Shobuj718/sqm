<x-layouts.app>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Roles Management') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create roles, assign permissions, and keep your access control easy to understand.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Back to Dashboard
                </a>
                <button onclick="showCreateRoleModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Create Role
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

            <div class="grid gap-6 mb-8 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Roles Overview</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Each role bundles permissions so users can be granted access consistently.</p>

                        <div class="mt-6">
                            <label for="roleSearch" class="sr-only">Search roles</label>
                            <input id="roleSearch" type="search" oninput="filterRoles()" placeholder="Search roles..." class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-4 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Why use roles?</h3>
                        <ul class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                Manage access by grouping related permissions.
                            </li>
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-green-600"></span>
                                Assign one role and the user gets all included permissions.
                            </li>
                            <li class="flex gap-3 items-start">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                                Keep permission assignment consistent and easy to audit.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach($roles as $role)
                    <div class="role-card bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $role->name }}</h3>
                                    @if($role->description)
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $role->description }}</p>
                                    @endif
                                </div>
                                <button onclick="showAssignPermissionModal({{ $role->id }}, '{{ $role->name }}')" class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">
                                    Add Permission
                                </button>
                            </div>

                            <div class="mt-5">
                                <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    <span>Permissions</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $role->permissions->count() }}</span>
                                </div>
                                @if($role->permissions->count())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($role->permissions as $permission)
                                            <div class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $permission->name }}
                                                <button onclick="removePermissionFromRole({{ $role->id }}, {{ json_encode($permission->name) }})" class="text-green-600 hover:text-green-900 dark:text-green-200 dark:hover:text-green-100">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No permissions assigned yet.</p>
                                @endif
                            </div>

                            <div class="mt-6 text-xs text-gray-500 dark:text-gray-400">Created {{ $role->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div id="createRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create New Role</h3>
                    <button onclick="closeCreateRoleModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.roles.create') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="role_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                        <input type="text" name="name" id="role_name" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                    </div>
                    <div class="mb-4">
                        <label for="role_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="role_description" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeCreateRoleModal()" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-sm font-medium text-white hover:bg-blue-700">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="assignPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assign Permission to <span id="roleName"></span></h3>
                    <button onclick="closeAssignPermissionModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="assignPermissionForm" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="permissionSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Permissions</label>
                        <input type="text" id="permissionSearch" oninput="filterPermissions()" placeholder="Type to search permissions..." class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div class="mb-4">
                        <label for="permission_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Permission</label>
                        <select name="permission" id="permission_select" class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" required>
                            <option value="">Choose a permission...</option>
                            @foreach(\App\Models\Permission::orderBy('name')->get() as $permission)
                                <option value="{{ $permission->name }}" data-description="{{ $permission->description }}">{{ $permission->name }} @if($permission->description) - {{ $permission->description }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeAssignPermissionModal()" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-sm font-medium text-white hover:bg-blue-700">Assign Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Store assigned permissions per role for modal filtering
        const rolePermissions = {};
        @foreach($roles as $role)
            rolePermissions[{{ $role->id }}] = @json($role->permissions->pluck('name'));
        @endforeach

        function showCreateRoleModal() {
            document.getElementById('createRoleModal').classList.remove('hidden');
        }

        function closeCreateRoleModal() {
            document.getElementById('createRoleModal').classList.add('hidden');
        }

        function showAssignPermissionModal(roleId, roleName) {
            document.getElementById('roleName').textContent = roleName;
            document.getElementById('assignPermissionForm').action = `/admin/roles/${roleId}/assign-permission`;
            document.getElementById('permissionSearch').value = '';
            document.getElementById('assignPermissionModal').setAttribute('data-role-id', roleId);
            filterPermissions();
            document.getElementById('assignPermissionModal').classList.remove('hidden');
        }

        function closeAssignPermissionModal() {
            document.getElementById('assignPermissionModal').classList.add('hidden');
        }

        function filterPermissions() {
            const filter = document.getElementById('permissionSearch').value.toLowerCase();
            const roleId = document.getElementById('assignPermissionModal').getAttribute('data-role-id');
            const assigned = rolePermissions[roleId] || [];
            const select = document.getElementById('permission_select');
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                const text = option.textContent.toLowerCase();
                const isAssigned = assigned.includes(option.value);
                option.style.display = text.includes(filter) && !isAssigned ? '' : 'none';
            });
        }

        function removePermissionFromRole(roleId, permissionName) {
            if (confirm('Are you sure you want to remove this permission from the role?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/roles/${roleId}/remove-permission`;

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

        function filterRoles() {
            const filter = document.getElementById('roleSearch').value.toLowerCase();
            document.querySelectorAll('.role-card').forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p') ? card.querySelector('p').textContent.toLowerCase() : '';
                card.style.display = title.includes(filter) || description.includes(filter) ? '' : 'none';
            });
        }
    </script>
</x-layouts.app>
