<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketLogService;
use Illuminate\Support\Facades\Log;

class TicketAssignmentService
{
    public function assignTicketFromPageQueue(Ticket $ticket): ?int
    {
        $facebookPage = $ticket->facebookPage;

        if (!$facebookPage) {
            Log::warning('TicketAssignmentService could not assign ticket because facebook page was not loaded.', [
                'ticket_id' => $ticket->id,
            ]);
            return null;
        }

        $queue = $facebookPage->supportQueues()->first();

        if (!$queue) {
            Log::info('No support queue assigned to Facebook page.', [
                'ticket_id' => $ticket->id,
                'facebook_page_id' => $facebookPage->page_id,
            ]);
            return null;
        }

        $agent = $queue->users()
            ->where('availability_status', User::STATUS_ONLINE)
            ->withCount(['tickets as active_ticket_count' => function ($query) {
                $query->whereIn('status', ['open', 'waiting']);
            }])
            ->orderBy('active_ticket_count')
            ->orderBy('id')
            ->first();

        if (!$agent) {
            Log::info('Support queue has no active agents.', [
                'ticket_id' => $ticket->id,
                'support_queue_id' => $queue->id,
            ]);
            return null;
        }

        $oldAssignedTo = $ticket->assigned_to;

        $ticket->update([
            'assigned_to' => $agent->id,
            'support_queue_id' => $queue->id,
        ]);

        if ($oldAssignedTo !== $agent->id) {
            TicketLogService::logAssignment(
                $ticket,
                $oldAssignedTo,
                $agent->id,
                null,
                $agent->name
            );
        }

        $agent->refreshAvailabilityStatusBasedOnLoad();

        return $agent->id;
    }
}
