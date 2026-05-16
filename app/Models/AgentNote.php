<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentNote extends Model
{
    protected $table = 'agent_notes';

    protected $fillable = ['user_id', 'content'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent who owns this note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
