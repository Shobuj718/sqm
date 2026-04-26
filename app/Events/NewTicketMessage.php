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

    public function __construct(
        public Ticket $ticket,
        public  $messageData
    ) {
        $this->ticketId = $ticket->id;
        $this->message = [
            'id' => $messageData->id,
            'message' => $messageData->message,
            'sender_type' => $messageData->sender_type,
            'channel' => $messageData->channel,
            'created_at' => $messageData->created_at->toIso8601String(),
        ];
        $this->eventType = 'new_message';
    }

    public function broadcastOn(): array
    {

        Log::info('Broadcasting NewTicketMessage', [
        'channel' => 'ticket.' . $this->ticket->id
    ]);

        return [
            new PrivateChannel('ticket.' . $this->ticketId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'message' => $this->message,
            'event_type' => $this->eventType,
        ];
    }

    public function broadcastAs()
    {
        return 'NewTicketMessage';
    }
}
