<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RagKnowledgeController extends Controller
{
    public function __construct(private readonly RagService $ragService)
    {
    }

    public function index(Request $request): View
    {
        $documents = RagDocument::query()
            ->with('facebookPage')
            ->withCount('chunks')
            ->when($request->filled('page'), fn ($query) => $query->where('facebook_page_id', $request->integer('page')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pages = FacebookPage::orderBy('page_name')->get(['id', 'page_name']);
        $searchResults = collect();
        $answer = null;

        if ($request->filled('q')) {
            $pageId = $request->filled('search_page_id') ? $request->integer('search_page_id') : null;

            if ($request->boolean('draft_reply')) {
                $ragAnswer = $this->ragService->answer((string) $request->string('q'), $pageId);
                $answer = $ragAnswer['answer'];
                $searchResults = $ragAnswer['matches'];
            } else {
                $searchResults = $this->ragService->search((string) $request->string('q'), $pageId);
            }
        }

        return view('admin.rag.index', compact('documents', 'pages', 'searchResults', 'answer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'facebook_page_id' => 'nullable|exists:facebook_pages,id',
            'source_type' => 'nullable|string|max:100',
            'source_reference' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'knowledge_file' => 'nullable|file|max:10240|mimes:txt,md,csv,json',
        ]);

        $content = (string) ($validated['content'] ?? '');

        if ($request->hasFile('knowledge_file')) {
            $content = file_get_contents($request->file('knowledge_file')->getRealPath()) ?: $content;
            $validated['source_reference'] = $validated['source_reference']
                ?? $request->file('knowledge_file')->getClientOriginalName();
            $validated['source_type'] = $validated['source_type'] ?? 'file';
        }

        if (trim($content) === '') {
            return back()
                ->withErrors(['content' => 'Paste text or upload a supported knowledge file.'])
                ->withInput();
        }

        try {
            $this->ragService->ingestText(
                title: $validated['title'],
                content: $content,
                facebookPageId: $validated['facebook_page_id'] ?? null,
                sourceType: $validated['source_type'] ?? 'manual',
                sourceReference: $validated['source_reference'] ?? null,
                metadata: [
                    'uploaded_by' => $request->user()?->id,
                ]
            );
        } catch (\Throwable $exception) {
            return back()
                ->withErrors(['content' => 'Embedding failed: ' . $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('rag.index')
            ->with('status', 'Knowledge embedded successfully.');
    }

    public function show(RagDocument $document): View
    {
        $document->load(['facebookPage', 'chunks']);

        return view('admin.rag.show', compact('document'));
    }

    public function rebuild(RagDocument $document): RedirectResponse
    {
        try {
            $this->ragService->embedDocument($document);
        } catch (\Throwable $exception) {
            return back()->withErrors(['content' => 'Embedding failed: ' . $exception->getMessage()]);
        }

        return redirect()
            ->route('rag.show', $document)
            ->with('status', 'Document embeddings rebuilt.');
    }

    public function destroy(RagDocument $document): RedirectResponse
    {
        $document->delete();

        return redirect()
            ->route('rag.index')
            ->with('status', 'Knowledge document deleted.');
    }
}
