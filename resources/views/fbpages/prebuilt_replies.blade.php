<x-layouts.app>

<div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between mb-8">
    <div>
        <h1 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">Reply Dataset Manager</h1>
        <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Manage your page-specific reply dataset with quick edits, search, and JSON-backed persistence.
            Use the form below to add new keyword replies for Facebook comments.
        </p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            Page ID: <span class="font-medium">{{ $pageId }}</span>
        </div>
        <a href="{{ route('pages') }}"
           class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            ← Back 
        </a>
    </div>
</div>

@if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-900 shadow-sm dark:border-green-700 dark:bg-green-900/20 dark:text-green-100">
        {{ session('success') }}
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[420px_1fr]">
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Add a new reply</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Add a new keyword and reply pair. This dataset is stored in a page-specific JSON file.
            </p>
        </div>

        <form method="POST" action="{{ url('/replies/add') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="page_id" value="{{ $pageId }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment keyword</label>
                <input type="text"
                       name="comment"
                       class="mt-2 block w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                       placeholder="Enter keyword or full comment"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reply message</label>
                <textarea name="reply"
                          rows="4"
                          class="mt-2 block w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                          placeholder="Enter the reply message"
                          required></textarea>
            </div>

            <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">
                Add Reply
            </button>
        </form>
    </section>

    <section class="space-y-6">
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Reply list</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Search across all replies and navigate pages for large datasets.</p>
                </div>
                <form method="GET" action="{{ url("/replies/{$pageId}") }}" class="flex w-full max-w-sm items-center gap-2">
                    <input name="search"
                           value="{{ $search }}"
                           class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                           placeholder="Search replies...">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-blue-600 dark:hover:bg-blue-500">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.15em] text-gray-500 dark:text-gray-400">Total replies</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $total }}</p>
                </div>
                <div class="rounded-2xl bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    Page {{ $page }} of {{ max(1, ceil($total / $perPage)) }}
                </div>
            </div>

            <div class="space-y-4">
                @forelse($items as $index => $item)
                    <div data-reply-card class="rounded-3xl border border-gray-200 bg-gray-50 p-5 shadow-sm transition hover:border-blue-300 dark:border-gray-700 dark:bg-gray-950">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-4 lg:max-w-[65%]">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Comment</p>
                                    <p class="mt-2 whitespace-pre-line rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ $item['comment'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Reply</p>
                                    <p class="mt-2 whitespace-pre-line rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ $item['reply'] }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:w-48">
                                <details class="rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-blue-300 dark:border-gray-700 dark:bg-gray-950">
                                    <summary class="cursor-pointer text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Edit reply</summary>
                                    <form method="POST" action="{{ url('/replies/update') }}" class="mt-4 space-y-4">
                                        @csrf
                                        <input type="hidden" name="page_id" value="{{ $pageId }}">
                                        <input type="hidden" name="index" value="{{ ($page - 1) * $perPage + $index }}">

                                        <div>
                                            <label class="block text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Comment</label>
                                            <input type="text"
                                                   name="comment"
                                                   value="{{ $item['comment'] }}"
                                                   class="mt-2 block w-full rounded-2xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                        </div>

                                        <div>
                                            <label class="block text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Reply</label>
                                            <textarea name="reply"
                                                      rows="3"
                                                      class="mt-2 block w-full rounded-2xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ $item['reply'] }}</textarea>
                                        </div>

                                        <button type="submit"
                                                class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">
                                            Save changes
                                        </button>
                                    </form>
                                </details>

                                <form method="POST" action="{{ url('/replies/delete') }}">
                                    @csrf
                                    <input type="hidden" name="page_id" value="{{ $pageId }}">
                                    <input type="hidden" name="index" value="{{ ($page - 1) * $perPage + $index }}">
                                    <button type="submit"
                                            onclick="return confirm('Delete this reply?')"
                                            class="inline-flex w-full items-center justify-center rounded-2xl border border-red-500 bg-transparent px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-500/10 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-600/10">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                        No replies available yet. Add the first reply using the form on the left.
                    </div>
                @endforelse
            </div>

            @if($total > $perPage)
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ ($page - 1) * $perPage + 1 }} to {{ min($page * $perPage, $total) }} of {{ $total }} replies
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $lastPage = max(1, ceil($total / $perPage));
                            $start = max(1, $page - 2);
                            $end = min($lastPage, $page + 2);
                        @endphp

                        @if($page > 1)
                            <a href="{{ url("/replies/{$pageId}") }}?page={{ $page - 1 }}&search={{ urlencode($search) }}"
                               class="rounded-2xl border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                                Previous
                            </a>
                        @endif

                        @for($p = $start; $p <= $end; $p++)
                            <a href="{{ url("/replies/{$pageId}") }}?page={{ $p }}&search={{ urlencode($search) }}"
                               class="rounded-2xl px-4 py-2 text-sm transition {{ $p === $page ? 'bg-blue-600 text-white dark:bg-blue-500' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200' }}">
                                {{ $p }}
                            </a>
                        @endfor

                        @if($page < $lastPage)
                            <a href="{{ url("/replies/{$pageId}") }}?page={{ $page + 1 }}&search={{ urlencode($search) }}"
                               class="rounded-2xl border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                                Next
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>

</x-layouts.app>
