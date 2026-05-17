<x-layouts.app>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">RAG Knowledge Base</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Embed company data for page-aware AI replies.</p>
            </div>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="space-y-6">
            <form method="GET" action="{{ route('rag.index') }}" class="rounded-md border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_140px]">
                    <div>
                        <label for="q" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Search knowledge</label>
                        <input id="q" name="q" value="{{ request('q') }}" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="Customer question or keyword">
                    </div>
                    <div>
                        <label for="search_page_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Page scope</label>
                        <select id="search_page_id" name="search_page_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">All/global</option>
                            @foreach ($pages as $page)
                                <option value="{{ $page->id }}" @selected((string) request('search_page_id') === (string) $page->id)>{{ $page->page_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Search</button>
                    </div>
                </div>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="draft_reply" value="1" @checked(request()->boolean('draft_reply')) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Draft reply from retrieved knowledge
                </label>
            </form>

            @if (request('q'))
                <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Retrieval Results</h2>

                    @if ($answer)
                        <div class="mb-5 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                            <div class="mb-1 font-medium">Draft reply</div>
                            <p>{{ $answer }}</p>
                        </div>
                    @endif

                    <div class="space-y-4">
                        @forelse ($searchResults as $result)
                            <article class="border-b border-gray-200 pb-4 last:border-0 last:pb-0 dark:border-gray-700">
                                <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $result['document']->title }}</span>
                                    <span>{{ optional($result['document']->facebookPage)->page_name ?? 'Global' }}</span>
                                    <span>Score {{ number_format($result['score'], 3) }}</span>
                                </div>
                                <p class="whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-300">{{ Str::limit($result['content'], 900) }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-gray-600 dark:text-gray-400">No matching embedded knowledge found.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Documents</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Uploaded and embedded company knowledge.</p>
                        </div>
                        <form method="GET" action="{{ route('rag.index') }}" class="flex flex-wrap gap-2">
                            <select name="page" class="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">All pages</option>
                                @foreach ($pages as $page)
                                    <option value="{{ $page->id }}" @selected((string) request('page') === (string) $page->id)>{{ $page->page_name }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Any status</option>
                                @foreach ([\App\Models\RagDocument::STATUS_PENDING, \App\Models\RagDocument::STATUS_EMBEDDED, \App\Models\RagDocument::STATUS_FAILED] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-md border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Filter</button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Title</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Page</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Chunks</th>
                                <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-5 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('rag.show', $document) }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">{{ $document->title }}</a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $document->source_type }}{{ $document->source_reference ? ' · '.$document->source_reference : '' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ optional($document->facebookPage)->page_name ?? 'Global' }}</td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">{{ $document->chunks_count }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium @class([
                                            'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' => $document->status === \App\Models\RagDocument::STATUS_EMBEDDED,
                                            'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' => $document->status === \App\Models\RagDocument::STATUS_FAILED,
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' => $document->status === \App\Models\RagDocument::STATUS_PENDING,
                                        ])">{{ ucfirst($document->status) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('rag.show', $document) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">No knowledge documents embedded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 p-5 dark:border-gray-700">
                    {{ $documents->links() }}
                </div>
            </section>
        </section>

        <aside>
            <form method="POST" action="{{ route('rag.store') }}" enctype="multipart/form-data" class="rounded-md border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                @csrf
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Add Knowledge</h2>
                <div class="space-y-4">
                    <div>
                        <label for="title" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
                        <input id="title" name="title" type="text" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="Return policy, product FAQ, delivery rules">
                    </div>
                    <div>
                        <label for="facebook_page_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Facebook page</label>
                        <select id="facebook_page_id" name="facebook_page_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Global knowledge</option>
                            @foreach ($pages as $page)
                                <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="source_type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                            <input id="source_type" name="source_type" type="text" value="manual" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label for="source_reference" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Reference</label>
                            <input id="source_reference" name="source_reference" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    <div>
                        <label for="content" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Paste text</label>
                        <textarea id="content" name="content" rows="10" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="Paste FAQs, policies, product details, scripts, or SOPs"></textarea>
                    </div>
                    <div>
                        <label for="knowledge_file" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Or upload file</label>
                        <input id="knowledge_file" name="knowledge_file" type="file" accept=".txt,.md,.csv,.json" class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-700 dark:text-gray-300 dark:file:bg-gray-700">
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Embed Knowledge</button>
                </div>
            </form>
        </aside>
    </div>
</x-layouts.app>
