<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'facebook_message_id',
        'sender_facebook_id',
        'message',
        'message_type',
        'channel',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket that this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Check if this message is from a customer.
     */
    public function isFromCustomer(): bool
    {
        return $this->message_type === 'customer';
    }

    /**
     * Check if this message is from an agent.
     */
    public function isFromAgent(): bool
    {
        return $this->message_type === 'agent';
    }

    /**
     * Check if this message is a system message.
     */
    public function isSystemMessage(): bool
    {
        return $this->message_type === 'system';
    }
}

