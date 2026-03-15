<x-layouts.app>

<div class="flex items-center justify-between mb-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Post Comments
        </h1>
        <p class="text-sm text-gray-500">
            Post ID : {{ $post_id }}
        </p>
    </div>

    <a href="{{ url()->previous() }}"
       class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
        ← Back
    </a>

</div>


@if(empty($comments['data']))

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-10 text-center">
    <p class="text-gray-500">No comments found for this post.</p>
</div>

@else


<div class="space-y-5">

@foreach ($comments['data'] as $comment)

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition">

    <div class="flex items-start gap-4">

        {{-- Profile Image --}}
        <img src="https://graph.facebook.com/{{ $comment['from']['id'] ?? '' }}/picture?type=small"
             class="w-10 h-10 rounded-full">


        <div class="flex-1">

            {{-- Comment Header --}}
            <div class="flex items-center justify-between">

                <h3 class="font-semibold text-gray-800 dark:text-gray-100">
                    {{ $comment['from']['name'] ?? 'Anonymous' }}
                </h3>

                <span class="text-xs text-gray-400">
                    {{ \Carbon\Carbon::parse($comment['created_time'])->diffForHumans() }}
                </span>

            </div>


            {{-- Comment Message --}}
            <p class="text-gray-700 dark:text-gray-200 mt-1 text-sm leading-relaxed">
                {{ $comment['message'] }}
            </p>


            {{-- Reply Box --}}
            <form action="{{ route('fbpages.replyComment', $comment['id']) }}"
                  method="POST"
                  class="mt-3 flex items-center gap-2">

                @csrf

                <input
                    type="text"
                    name="message"
                    placeholder="Write a reply..."
                    required
                    class="flex-1 px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">

                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 flex items-center gap-1">

                    Reply

                </button>

            </form>

        </div>

    </div>

</div>

@endforeach

</div>

@endif

</x-layouts.app>
