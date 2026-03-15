@props(['sortable' => false, 'field' => null, 'direction' => null])

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider']) }}>
    @if($sortable)
        <div class="flex items-center cursor-pointer" wire:click="sortBy('{{ $field }}')">
            {{ $slot }}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
            </svg>
        </div>
    @else
        {{ $slot }}
    @endif
</th>
