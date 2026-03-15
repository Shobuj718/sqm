<div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
    <div class="flex flex-wrap items-center gap-3">
        <!-- Status Filter -->
        <div class="flex items-center">
            <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Status:</label>
            <select id="status-filter" wire:model.live="statusFilter"
                    class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
            </select>
        </div>

        <!-- Role Filter -->
        <div class="flex items-center">
            <label for="role-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Role:</label>
            <select id="role-filter" wire:model.live="roleFilter"
                    class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="editor">Editor</option>
                <option value="user">User</option>
            </select>
        </div>

        <!-- Clear Filters -->
        <button wire:click="clearFilters" class="text-sm text-blue-600 dark:text-blue-400 hover:underline ml-auto">
            Clear Filters
        </button>
    </div>
</div>
