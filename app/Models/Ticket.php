<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'facebook_page_id',
        'customer_facebook_id',
        'customer_name',
        'subject',
        'initial_message',
        'status',
        'priority',
        'assigned_to',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the Facebook page associated with this ticket.
     */
    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id', 'page_id');
    }

    /**
     * Get the agent assigned to this ticket.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all messages in this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get all customer messages in this ticket.
     */
    public function customerMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)
            ->where('message_type', 'customer')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all agent messages in this ticket.
     */
    public function agentMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)
            ->where('message_type', 'agent')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all logs for this ticket.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if ticket is open.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    /**
     * Close the ticket.
     */
    public function close(): self
    {
        $this->update(['status' => 'closed']);
        return $this;
    }

    /**
     * Resolve the ticket.
     */
    public function resolve(): self
    {
        $this->update(['status' => 'resolved']);
        return $this;
    }

    /**
     * Get the latest message in this ticket.
     */
    public function getLatestMessage(): ?SupportMessage
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Add a message to this ticket.
     */
    public function addMessage(string $facebookMessageId, string $senderFacebookId, string $message, string $messageType = 'customer', string $channel = 'messenger'): SupportMessage
    {
        return $this->messages()->create([
            'facebook_message_id' => $facebookMessageId,
            'sender_facebook_id' => $senderFacebookId,
            'message' => $message,
            'message_type' => $messageType,
            'channel' => $channel,
        ]);
    }
}

