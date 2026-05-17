<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RagDocument extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_EMBEDDED = 'embedded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'facebook_page_id',
        'title',
        'source_type',
        'source_reference',
        'content',
        'content_hash',
        'status',
        'metadata',
        'embedded_at',
        'error',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedded_at' => 'datetime',
    ];

    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(RagChunk::class)->orderBy('chunk_index');
    }
}
