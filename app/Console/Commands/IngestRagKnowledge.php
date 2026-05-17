<?php

namespace App\Console\Commands;

use App\Models\FacebookPage;
use App\Services\RagService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IngestRagKnowledge extends Command
{
    protected $signature = 'rag:ingest
        {path : File or directory to ingest}
        {--title= : Title for a single imported file}
        {--page= : Facebook page database ID or page_id to scope this knowledge}
        {--source=file : Source type label}
        {--recursive : Import supported files inside nested directories}';

    protected $description = 'Embed company knowledge into the local RAG store.';

    public function handle(RagService $ragService): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (!File::exists($path)) {
            $this->error("Path does not exist: {$path}");

            return self::FAILURE;
        }

        $facebookPageId = $this->resolveFacebookPageId($this->option('page'));
        $files = File::isDirectory($path) ? $this->filesFromDirectory($path) : [new \SplFileInfo($path)];

        if (empty($files)) {
            $this->warn('No supported files found.');

            return self::SUCCESS;
        }

        $imported = 0;

        foreach ($files as $file) {
            $realPath = $file->getRealPath();

            if (!$realPath || !$this->isSupportedFile($realPath)) {
                continue;
            }

            $title = $this->option('title') ?: pathinfo($realPath, PATHINFO_FILENAME);
            $content = File::get($realPath);

            try {
                $document = $ragService->ingestText(
                    title: $title,
                    content: $content,
                    facebookPageId: $facebookPageId,
                    sourceType: (string) $this->option('source'),
                    sourceReference: str_replace(base_path() . DIRECTORY_SEPARATOR, '', $realPath),
                );

                $this->info("Embedded #{$document->id}: {$document->title} ({$document->chunks()->count()} chunks)");
                $imported++;
            } catch (\Throwable $exception) {
                $this->error("Failed {$realPath}: {$exception->getMessage()}");
            }
        }

        $this->info("Done. Imported {$imported} document(s).");

        return self::SUCCESS;
    }

    private function filesFromDirectory(string $path): array
    {
        $files = $this->option('recursive') ? File::allFiles($path) : File::files($path);

        return array_values(array_filter($files, fn (\SplFileInfo $file): bool => $this->isSupportedFile($file->getRealPath())));
    }

    private function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    private function isSupportedFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['txt', 'md', 'csv', 'json'], true);
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
