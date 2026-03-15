<x-layouts.app>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
        Facebook Pages
    </h1>

    <input type="text"
           id="pageSearch"
           placeholder="Search page..."
           class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white"
           onkeyup="searchPages()">
</div>


{{-- Page Count Card --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <p class="text-gray-500 text-sm">Total Pages</p>
        <p class="text-2xl font-bold">{{ count($pages['data']) }}</p>
    </div>

</div>


<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">

    <table class="min-w-full text-sm text-left">

        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200">
            <tr>
                <th class="px-6 py-3">Page</th>
                <th class="px-6 py-3">Page ID</th>
                <th class="px-6 py-3">Category</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>

        <tbody id="pagesTable" class="divide-y dark:divide-gray-700">

        @forelse($pages['data'] as $page)

            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">

                {{-- Page Name --}}
                <td class="px-6 py-4 flex items-center gap-3">

                    <img src="https://graph.facebook.com/{{ $page['id'] }}/picture?type=small"
                         class="w-10 h-10 rounded-full">

                    <div>
                        <a href="https://www.facebook.com/{{ $page['id'] }}"
                           target="_blank"
                           class="font-medium text-blue-600 hover:underline">
                           {{ $page['name'] }}
                        </a>
                    </div>

                </td>


                {{-- Page ID --}}
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                    {{ $page['id'] }}
                </td>


                {{-- Category --}}
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                    {{ $page['category'] ?? 'N/A' }}
                </td>


                {{-- Actions --}}
                <td class="px-6 py-4 text-center">

                    <a href="{{ route('fbpages.posts', $page['id']) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">

                        View Posts

                    </a>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="4" class="text-center py-8 text-gray-500">
                    No Facebook pages found
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

</div>


<script>

function searchPages() {

    let input = document.getElementById("pageSearch").value.toLowerCase();

    let rows = document.querySelectorAll("#pagesTable tr");

    rows.forEach(row => {

        let text = row.innerText.toLowerCase();

        row.style.display = text.includes(input) ? "" : "none";

    });

}

</script>

</x-layouts.app>
