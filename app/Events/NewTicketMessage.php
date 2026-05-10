<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class NewTicketMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $ticketId;
    public array $message;
    public string $eventType;
    public bool $isNewTicket;

    public function __construct(
        public Ticket $ticket,
        public  $messageData,
        bool $isNewTicket = false
    ) {
        $this->ticketId = $ticket->id;
        $this->message = [
            'id' => $messageData->id,
            'message' => $messageData->message,
            'message_type' => $messageData->message_type,
            'sender_type' => $messageData->message_type,
            'channel' => $messageData->channel,
            'created_at' => $messageData->created_at->toIso8601String(),
            'customer_name' => $ticket->customer_name,
            'facebook_page_name' => optional($ticket->facebookPage)->page_name,
            'attachments' => $messageData->attachments ?? [],
        ];
        $this->isNewTicket = $isNewTicket;
        $this->eventType = $this->isNewTicket ? 'new_ticket' : 'new_message';
    }

    public function broadcastOn(): array
    {
        Log::info('Broadcasting NewTicketMessage', [
            'channels' => array_filter([
                'ticket.' . $this->ticket->id,
                $this->ticket->assigned_to ? 'App.Models.User.' . $this->ticket->assigned_to : null,
            ]),
        ]);

        $channels = [
            new PrivateChannel('ticket.' . $this->ticketId),
        ];

        if ($this->ticket->assigned_to) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->ticket->assigned_to);
        }

        if ($this->isNewTicket) {
            $channels[] = new PrivateChannel('tickets.new');
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'message' => $this->message,
            'event_type' => $this->eventType,
            'ticket' => [
                'id' => $this->ticketId,
                'customer_name' => $this->ticket->customer_name,
                'customer_facebook_id' => $this->ticket->customer_facebook_id,
                'subject' => $this->ticket->subject,
                'facebook_page_name' => optional($this->ticket->facebookPage)->name,
                'updated_at' => optional($this->ticket->updated_at)->toIso8601String(),
                'unread_count' => 1,
            ],
        ];
    }

    public function broadcastAs()
    {
        return 'NewTicketMessage';
    }
}
