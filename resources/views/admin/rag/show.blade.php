<x-layouts.app>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $document->title }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ optional($document->facebookPage)->page_name ?? 'Global knowledge' }}</p>
            </div>
            <a href="{{ route('rag.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Back</a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="rounded-md border border-gray-200 bg-white p-5 text-sm dark:border-gray-700 dark:bg-gray-800">
            <dl class="space-y-3">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ ucfirst($document->status) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Source</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document->source_type }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Reference</dt>
                    <dd class="break-words font-medium text-gray-900 dark:text-white">{{ $document->source_reference ?? 'None' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Chunks</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document->chunks->count() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Embedded</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ optional($document->embedded_at)->format('M d, Y H:i') ?? 'Not embedded' }}</dd>
                </div>
            </dl>

            @if ($document->error)
                <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-3 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    {{ $document->error }}
                </div>
            @endif

            <div class="mt-5 flex flex-col gap-2">
                <form method="POST" action="{{ route('rag.rebuild', $document) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Rebuild Embeddings</button>
                </form>
                <form method="POST" action="{{ route('rag.destroy', $document) }}" onsubmit="return confirm('Delete this knowledge document?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950">Delete</button>
                </form>
            </div>
        </aside>

        <section class="rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Embedded Chunks</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($document->chunks as $chunk)
                    <article class="p-5">
                        <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Chunk {{ $chunk->chunk_index + 1 }}</span>
                            <span>{{ $chunk->embedding_model }}</span>
                            <span>{{ $chunk->embedding_dimensions }} dimensions</span>
                        </div>
                        <p class="whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $chunk->content }}</p>
                    </article>
                @empty
                    <p class="p-5 text-sm text-gray-600 dark:text-gray-400">This document has no chunks yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
