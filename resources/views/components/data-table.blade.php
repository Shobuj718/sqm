<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
    <!-- Table Header -->
    @if(isset($header))
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
            {{ $header }}
        </div>
    @endif

    <!-- Search & Filters -->
    @if($searchable ?? true)
        <x-table-search />
    @endif

    @if($filterable ?? true)
        <x-table-filters />
    @endif

    <!-- Actions Bar -->
    @if(isset($actions))
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            {{ $actions }}
        </div>
    @endif

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/80">
                <tr>
                    @if($selectable ?? false)
                        <th class="px-6 py-3">
                            <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </th>
                    @endif
                    {{ $columns }}
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {{ $rows }}
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($paginated ?? true)
        <x-table-pagination :data="$data" />
    @endif
</div>
