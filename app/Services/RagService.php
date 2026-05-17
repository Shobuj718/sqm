<?php

namespace App\Services;

use App\Models\RagChunk;
use App\Models\RagDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RagService
{
    public function ingestText(
        string $title,
        string $content,
        ?int $facebookPageId = null,
        string $sourceType = 'manual',
        ?string $sourceReference = null,
        array $metadata = []
    ): RagDocument {
        $content = $this->normalizeText($content);

        if ($content === '') {
            throw new \InvalidArgumentException('Knowledge content cannot be empty.');
        }

        $document = RagDocument::create([
            'facebook_page_id' => $facebookPageId,
            'title' => $title,
            'source_type' => $sourceType,
            'source_reference' => $sourceReference,
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'status' => RagDocument::STATUS_PENDING,
            'metadata' => array_merge($metadata, [
                'character_count' => Str::length($content),
            ]),
        ]);

        return $this->embedDocument($document, $content);
    }

    public function embedDocument(RagDocument $document, ?string $content = null): RagDocument
    {
        $content ??= (string) $document->content;
        $chunks = $this->chunkText($content);

        if (empty($chunks)) {
            $document->update([
                'status' => RagDocument::STATUS_FAILED,
                'error' => 'No text chunks were created from this document.',
            ]);

            return $document;
        }

        try {
            $embeddings = $this->createEmbeddings($chunks);
            $model = config('rag.embedding_model');

            DB::transaction(function () use ($document, $chunks, $embeddings, $model): void {
                $document->chunks()->delete();

                foreach ($chunks as $index => $chunk) {
                    $embedding = $embeddings[$index] ?? null;

                    if (!is_array($embedding) || empty($embedding)) {
                        throw new \RuntimeException("Missing embedding for chunk {$index}.");
                    }

                    RagChunk::create([
                        'rag_document_id' => $document->id,
                        'chunk_index' => $index,
                        'content' => $chunk,
                        'content_hash' => hash('sha256', $chunk),
                        'embedding_model' => $model,
                        'embedding_dimensions' => count($embedding),
                        'embedding' => $embedding,
                        'metadata' => [
                            'character_count' => Str::length($chunk),
                        ],
                    ]);
                }

                $document->update([
                    'status' => RagDocument::STATUS_EMBEDDED,
                    'embedded_at' => now(),
                    'error' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            $document->update([
                'status' => RagDocument::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $document->fresh(['chunks', 'facebookPage']);
    }

    public function search(string $query, ?int $facebookPageId = null, ?int $limit = null, ?float $minScore = null): Collection
    {
        $query = $this->normalizeText($query);

        if ($query === '') {
            return collect();
        }

        $queryEmbedding = $this->createEmbedding($query);
        $limit ??= config('rag.top_k');
        $minScore ??= config('rag.min_score');

        $chunks = RagChunk::query()
            ->with(['document.facebookPage'])
            ->where('embedding_model', config('rag.embedding_model'))
            ->whereHas('document', function (Builder $documentQuery) use ($facebookPageId): void {
                $documentQuery->where('status', RagDocument::STATUS_EMBEDDED);

                if ($facebookPageId) {
                    $documentQuery->where(function (Builder $pageQuery) use ($facebookPageId): void {
                        $pageQuery
                            ->whereNull('facebook_page_id')
                            ->orWhere('facebook_page_id', $facebookPageId);
                    });
                }
            })
            ->latest()
            ->limit(config('rag.max_candidates'))
            ->get();

        return $chunks
            ->map(function (RagChunk $chunk) use ($queryEmbedding): array {
                return [
                    'chunk' => $chunk,
                    'document' => $chunk->document,
                    'score' => $this->cosineSimilarity($queryEmbedding, $chunk->embedding ?? []),
                    'content' => $chunk->content,
                ];
            })
            ->filter(fn (array $result): bool => $result['score'] >= $minScore)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function answer(string $question, ?int $facebookPageId = null): array
    {
        $matches = $this->search($question, $facebookPageId);
        $context = $matches
            ->map(fn (array $match): string => $match['content'])
            ->implode("\n\n---\n\n");

        if ($context === '') {
            return [
                'answer' => null,
                'matches' => $matches,
            ];
        }

        $client = app('openai');
        $response = $client->chat()->create([
            'model' => config('rag.chat_model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a customer support assistant. Answer only from the supplied company knowledge. If the answer is not present, say that an agent should review it.',
                ],
                [
                    'role' => 'user',
                    'content' => "Company knowledge:\n{$context}\n\nCustomer question:\n{$question}\n\nDraft a concise, professional reply.",
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 300,
        ]);

        return [
            'answer' => trim($response->choices[0]->message->content ?? ''),
            'matches' => $matches,
        ];
    }

    public function chunkText(string $content): array
    {
        $content = $this->normalizeText($content);
        $chunkSize = max(500, (int) config('rag.chunk_size'));
        $overlap = max(0, min((int) config('rag.chunk_overlap'), $chunkSize - 1));

        if ($content === '') {
            return [];
        }

        $paragraphs = preg_split("/\n{2,}/", $content) ?: [$content];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (Str::length($paragraph) > $chunkSize) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }

                foreach ($this->splitLongText($paragraph, $chunkSize, $overlap) as $piece) {
                    $chunks[] = $piece;
                }

                continue;
            }

            $candidate = trim($current . "\n\n" . $paragraph);

            if ($current !== '' && Str::length($candidate) > $chunkSize) {
                $chunks[] = $current;
                $current = $overlap > 0 ? trim(Str::substr($current, -$overlap) . "\n\n" . $paragraph) : $paragraph;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter($chunks));
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $left = (float) $a[$i];
            $right = (float) $b[$i];
            $dot += $left * $right;
            $normA += $left * $left;
            $normB += $right * $right;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function createEmbedding(string $input): array
    {
        return $this->createEmbeddings([$input])[0] ?? [];
    }

    private function createEmbeddings(array $inputs): array
    {
        $parameters = [
            'model' => config('rag.embedding_model'),
            'input' => array_values($inputs),
        ];

        if (config('rag.embedding_dimensions')) {
            $parameters['dimensions'] = config('rag.embedding_dimensions');
        }

        $response = app('openai')->embeddings()->create($parameters);

        return collect($response->embeddings)
            ->sortBy(fn ($embedding) => $embedding->index ?? 0)
            ->map(fn ($embedding): array => $embedding->embedding)
            ->values()
            ->all();
    }

    private function splitLongText(string $text, int $chunkSize, int $overlap): array
    {
        $pieces = [];
        $offset = 0;
        $length = Str::length($text);

        while ($offset < $length) {
            $piece = trim(Str::substr($text, $offset, $chunkSize));

            if ($piece !== '') {
                $pieces[] = $piece;
            }

            $offset += $chunkSize - $overlap;
        }

        return $pieces;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
