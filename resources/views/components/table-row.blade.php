@props(['selectable' => false])

<tr {{ $attributes->merge(['class' => 'hover:bg-gray-50 dark:hover:bg-gray-700/50']) }}>
    @if($selectable)
        <td class="px-6 py-4 whitespace-nowrap">
            <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        </td>
    @endif
    {{ $slot }}
</tr>
