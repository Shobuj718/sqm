<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket,
        protected SupportMessage $message
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New message on ticket #{$this->ticket->id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new message has been added to ticket #{$this->ticket->id}: {$this->ticket->subject}")
            ->line("Customer: {$this->ticket->customer_name}")
            ->line("Message: " . substr($this->message->message, 0, 100) . (strlen($this->message->message) > 100 ? '...' : ''))
            ->action('View Ticket', route('tickets.show', $this->ticket->id))
            ->line('Thank you for using our support system!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'message_id' => $this->message->id,
            'customer_name' => $this->ticket->customer_name,
            'subject' => $this->ticket->subject,
            'message_preview' => substr($this->message->message, 0, 100),
            'url' => route('tickets.show', $this->ticket->id),
        ];
    }
}
