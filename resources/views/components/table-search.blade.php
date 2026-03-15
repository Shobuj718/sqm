<div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">{{ $title ?? 'Data Table' }}</h3>
        <div class="relative">
            <input type="text"
                   placeholder="{{ $placeholder ?? 'Search...' }}"
                   wire:model.live="search"
                   class="w-full md:w-64 pl-10 pr-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>
</div>
