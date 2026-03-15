@props(['data'])

<div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ $data->firstItem() }}</span>
                to
                <span class="font-medium">{{ $data->lastItem() }}</span>
                of
                <span class="font-medium">{{ $data->total() }}</span>
                results
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <label for="per-page" class="text-sm text-gray-700 dark:text-gray-300 mr-2">Per Page:</label>
            <select id="per-page" wire:model.live="perPage"
                    class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        {{ $data->links('components.pagination-links') }}
    </div>
</div>
