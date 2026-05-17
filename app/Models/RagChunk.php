<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagChunk extends Model
{
    protected $fillable = [
        'rag_document_id',
        'chunk_index',
        'content',
        'content_hash',
        'embedding_model',
        'embedding_dimensions',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(RagDocument::class, 'rag_document_id');
    }
}
