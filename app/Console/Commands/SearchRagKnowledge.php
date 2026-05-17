<?php

namespace App\Console\Commands;

use App\Models\FacebookPage;
use App\Services\RagService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SearchRagKnowledge extends Command
{
    protected $signature = 'rag:search
        {query : Question or search text}
        {--page= : Facebook page database ID or page_id to scope the search}
        {--limit=5 : Number of matching chunks to show}
        {--min-score= : Minimum cosine score}';

    protected $description = 'Search embedded RAG knowledge.';

    public function handle(RagService $ragService): int
    {
        $pageId = $this->resolveFacebookPageId($this->option('page'));
        $minScore = $this->option('min-score') !== null ? (float) $this->option('min-score') : null;
        $results = $ragService->search(
            query: (string) $this->argument('query'),
            facebookPageId: $pageId,
            limit: (int) $this->option('limit'),
            minScore: $minScore,
        );

        if ($results->isEmpty()) {
            $this->warn('No matches found.');

            return self::SUCCESS;
        }

        foreach ($results as $index => $result) {
            $document = $result['document'];
            $page = $document->facebookPage?->page_name ?? 'Global';
            $rank = $index + 1;

            $this->line("[{$rank}] {$document->title} ({$page}) score " . number_format($result['score'], 3));
            $this->line(Str::limit(str_replace("\n", ' ', $result['content']), 300));
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function resolveFacebookPageId(?string $page): ?int
    {
        if (!$page) {
            return null;
        }

        $facebookPage = FacebookPage::query()
            ->where('id', $page)
            ->orWhere('page_id', $page)
            ->first();

        if (!$facebookPage) {
            throw new \InvalidArgumentException("Facebook page not found: {$page}");
        }

        return $facebookPage->id;
    }
}
