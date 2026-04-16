<x-layouts.app>

<div class="flex items-center justify-between mb-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Facebook Posts
        </h1>

        <p class="text-sm text-gray-500">
            Page ID : {{ $page_id }}
        </p>
    </div>

    <a href="{{ route('pages') }}"
       class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
        ← Back to Pages
    </a>

</div>


@if(empty($posts['data']))

<div class="bg-white dark:bg-gray-800 p-10 rounded-lg shadow text-center">
    <p class="text-gray-500">No posts found for this page.</p>
</div>

@else

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

@foreach ($posts['data'] as $post)

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-lg transition duration-200">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">

        <span
            class="text-xs px-2 py-1 rounded-full font-medium
            {{ isset($post['message']) ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }}">

            {{ isset($post['message']) ? 'Post' : 'Story' }}

        </span>

        <span class="text-xs text-gray-400">
            {{ \Carbon\Carbon::parse($post['created_time'])->diffForHumans() }}
        </span>

    </div>


    {{-- Content --}}
    <div class="text-gray-700 dark:text-gray-200 text-sm mb-4 leading-relaxed">

        @if (!empty($post['message']))

            {{ \Illuminate\Support\Str::limit($post['message'], 180) }}

        @elseif(!empty($post['story']))

            {{ \Illuminate\Support\Str::limit($post['story'], 180) }}

        @else

            No content available

        @endif

    </div>


    {{-- Actions --}}
    <div class="flex items-center justify-between">

        @if (!empty($post['permalink_url']))
        <a href="{{ $post['permalink_url'] }}"
           target="_blank"
           class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-4 h-4"
                 fill="currentColor"
                 viewBox="0 0 24 24">
                <path d="M22 12.07C22 6.5 17.52 2 12 2S2 6.5 2 12.07c0 5.02 3.66 9.17 8.44 9.93v-7.02H7.9v-2.91h2.54V9.41c0-2.5 1.48-3.89 3.75-3.89 1.08 0 2.21.19 2.21.19v2.43h-1.25c-1.23 0-1.61.77-1.61 1.55v1.87h2.74l-.44 2.91h-2.3V22c4.78-.76 8.46-4.91 8.46-9.93z"/>
            </svg>

            View on Facebook
        </a>
        @endif


        <a href="{{ route('fbpages.comments', [$post['id'], $page_id]) }}"
           class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">

            View Comments

        </a>

    </div>

</div>

@endforeach

</div>

@endif

</x-layouts.app>
