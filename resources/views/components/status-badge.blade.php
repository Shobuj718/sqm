@props(['status'])

@php
$classes = match($status) {
    'active' => 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400',
    'inactive' => 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400',
    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400',
    default => 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-400'
};
@endphp

<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $classes }}">
    {{ $slot }}
</span>
