<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;

class TicketLogService
{
    /**
     * Log ticket creation.
     */
    public static function logCreated(Ticket $ticket): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Ticket created with subject: ' . $ticket->subject,
        ]);
    }

    /**
     * Log ticket status change.
     */
    public static function logStatusChange(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'status_changed',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ]);
    }

    /**
     * Log ticket priority change.
     */
    public static function logPriorityChange(Ticket $ticket, string $oldPriority, string $newPriority): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'priority_changed',
            'old_value' => $oldPriority,
            'new_value' => $newPriority,
        ]);
    }

    /**
     * Log ticket assignment.
     */
    public static function logAssignment(Ticket $ticket, ?int $oldAgentId, ?int $newAgentId, ?string $oldAgentName = null, ?string $newAgentName = null): void
    {
        if ($oldAgentId === null && $newAgentId !== null) {
            // Initial assignment
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'assigned',
                'new_value' => $newAgentName,
            ]);
        } elseif ($oldAgentId !== null && $newAgentId !== null && $oldAgentId !== $newAgentId) {
            // Reassignment
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'reassigned',
                'old_value' => $oldAgentName,
                'new_value' => $newAgentName,
            ]);
        }
    }

    /**
     * Log message addition.
     */
    public static function logMessageAdded(Ticket $ticket, string $messageType): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'message_added',
            'description' => 'New ' . $messageType . ' message added',
        ]);
    }

    /**
     * Log custom action.
     */
    public static function logAction(Ticket $ticket, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $description = null): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }
}
