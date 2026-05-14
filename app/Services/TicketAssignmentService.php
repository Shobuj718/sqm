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

        if (!$ticket->relationLoaded('assignedAgent')) {
            $ticket->load('assignedAgent');
        }

        $currentAgent = $ticket->assignedAgent;
        if ($currentAgent) {
            $activeCount = $currentAgent->tickets()
                ->whereIn('status', ['open', 'waiting'])
                ->count();

            if (!($currentAgent->availability_status !== User::STATUS_ONLINE && $activeCount > 25)) {
                Log::info('Keeping current ticket assignment for messenger ticket.', [
                    'ticket_id' => $ticket->id,
                    'assigned_to' => $currentAgent->id,
                    'availability_status' => $currentAgent->availability_status,
                    'active_ticket_count' => $activeCount,
                ]);
                return $currentAgent->id;
            }

            Log::info('Current assigned agent is offline and overloaded, looking for a new assignment.', [
                'ticket_id' => $ticket->id,
                'assigned_to' => $currentAgent->id,
                'availability_status' => $currentAgent->availability_status,
                'active_ticket_count' => $activeCount,
            ]);
        }

        $queue = $facebookPage->supportQueues()->first();

        if (!$queue) {
            Log::info('No support queue assigned to Facebook page.', [
                'ticket_id' => $ticket->id,
                'facebook_page_id' => $facebookPage->page_id,
            ]);
            return $currentAgent?->id;
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
            return $currentAgent?->id;
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
