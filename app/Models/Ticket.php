<?php

namespace App\Models;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'summary',
        'status',
        'priority',
        'assigned_to',
        'support_queue_id',
        'channel',
        'facebook_post_id',
        'facebook_comment_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'facebook_post_link',
    ];

    public function getFacebookPostLinkAttribute(): ?string
    {
        if (!empty($this->facebook_post_id)) {
            return 'https://www.facebook.com/' . $this->facebook_post_id;
        }
        return null;
    }

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

    public function supportQueue(): BelongsTo
    {
        return $this->belongsTo(SupportQueue::class, 'support_queue_id');
    }

    /**
     * Get all messages in this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this ticket.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    /**
     * Get tags assigned to this ticket.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'label_ticket', 'ticket_id', 'label_id');
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
     * Check if ticket is open or waiting for a response.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'waiting']);
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
        $this->update(['status' => 'solved']);
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
    public function addMessage(string $facebookMessageId, string $senderFacebookId, string $message, string $messageType = 'customer', string $channel = 'messenger', array $attachments = [], ?int $userId = null): SupportMessage
    {
        return $this->messages()->create([
            'facebook_message_id' => $facebookMessageId,
            'sender_facebook_id' => $senderFacebookId,
            'message' => $message,
            'attachments' => $attachments,
            'message_type' => $messageType,
            'channel' => $channel,
            'user_id' => $userId,
        ]);
    }
}

