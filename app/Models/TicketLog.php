<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket associated with this log.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getFormattedActionAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Ticket created',
            'assigned' => 'Assigned to ' . ($this->new_value ?? 'someone'),
            'reassigned' => 'Reassigned from ' . $this->old_value . ' to ' . $this->new_value,
            'status_changed' => 'Status changed from ' . ucfirst(str_replace('_', ' ', $this->old_value)) . ' to ' . ucfirst(str_replace('_', ' ', $this->new_value)),
            'priority_changed' => 'Priority changed from ' . ucfirst($this->old_value) . ' to ' . ucfirst($this->new_value),
            'message_added' => 'Message added',
            'closed' => 'Ticket closed',
            'resolved' => 'Ticket resolved',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
