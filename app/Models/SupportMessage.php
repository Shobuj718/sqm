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
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
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

    /**
     * Mark this message as read.
     */
    public function markAsRead(): self
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark this message as unread.
     */
    public function markAsUnread(): self
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
        return $this;
    }

    /**
     * Scope to get only unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get only read messages.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}

