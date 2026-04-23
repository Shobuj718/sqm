<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\NewTicketMessage;
use App\Services\TicketLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleFacebookMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public string $pageId;
    public string $pageToken;
    public ?string $senderId;
    public string $message;
    private bool $createdNewTicket = false;

    public function __construct(string $pageId, string $pageToken, ?string $senderId, string $message)
    {
        $this->pageId = $pageId;
        $this->pageToken = $pageToken;
        $this->senderId = $senderId;
        $this->message = $message;
    }

    public function handle(): void
    {
        if (empty($this->pageToken) || empty($this->senderId)) {
            Log::warning('HandleFacebookMessageJob skipped because required data is missing.', [
                'page_id' => $this->pageId,
                'sender_id' => $this->senderId,
            ]);
            return;
        }

        // Get or create ticket for this customer
        $ticket = $this->getOrCreateTicket();

        if (!$ticket) {
            Log::error('Failed to get or create ticket', [
                'page_id' => $this->pageId,
                'sender_id' => $this->senderId,
            ]);
            return;
        }

        // Add customer message to ticket
        $customerMessage = $ticket->addMessage(
            facebookMessageId: uniqid(),
            senderFacebookId: $this->senderId,
            message: $this->message,
            messageType: 'customer',
            channel: 'messenger'
        );

        // Notify the assigned agent about the new message
        if ($ticket->assigned_to && $ticket->assignedAgent) {
            $ticket->assignedAgent->notify(new NewTicketMessage($ticket, $customerMessage));
        }

        // Only send and store automatic reply for a newly created ticket.
        if ($this->createdNewTicket) {
            $reply = $this->checkDataset($this->message, $this->pageId)
                ?? $this->generateHFReply($this->message);

            // Save the automatic reply in the ticket history.
            $ticket->addMessage(
                facebookMessageId: uniqid('auto_reply_'),
                senderFacebookId: $this->pageId,
                message: $reply,
                messageType: 'agent',
                channel: 'messenger'
            );

            $response = Http::post('https://graph.facebook.com/v25.0/me/messages', [
                'access_token' => $this->pageToken,
                'recipient' => ['id' => $this->senderId],
                'message' => ['text' => $reply],
            ]);

            Log::info('HandleFacebookMessageJob response', [
                'page_id' => $this->pageId,
                'sender_id' => $this->senderId,
                'ticket_id' => $ticket->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        Log::info('HandleFacebookMessageJob response', [
            'page_id' => $this->pageId,
            'sender_id' => $this->senderId,
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Get existing ticket or create a new one for this customer.
     */
    private function getOrCreateTicket(): ?Ticket
    {
        try {
            // Check if a ticket already exists for this customer on this page
            $ticket = Ticket::where('facebook_page_id', $this->pageId)
                ->where('customer_facebook_id', $this->senderId)
                ->where('status', '!=', 'closed')
                ->first();

            if ($ticket) {
                // Ticket already exists, update status if needed
                if ($ticket->status === 'resolved') {
                    $ticket->update(['status' => 'in_progress']);
                }
                return $ticket;
            }

            // No existing ticket, create a new one
            $ticket = Ticket::create([
                'facebook_page_id' => $this->pageId,
                'customer_facebook_id' => $this->senderId,
                'customer_name' => $this->getCustomerName(),
                'subject' => 'Support Request - ' . now()->format('Y-m-d H:i'),
                'initial_message' => $this->message,
                'status' => 'open',
                'priority' => 'medium',
            ]);

            $this->createdNewTicket = true;

            // Log ticket creation
            TicketLogService::logAction(
                $ticket,
                'created',
                description: 'Ticket created automatically from customer message'
            );

            Log::info('New support ticket created', [
                'ticket_id' => $ticket->id,
                'customer_id' => $this->senderId,
                'page_id' => $this->pageId,
            ]);

            return $ticket;
        } catch (\Exception $e) {
            Log::error('Error creating/getting ticket', [
                'error' => $e->getMessage(),
                'page_id' => $this->pageId,
                'sender_id' => $this->senderId,
            ]);
            return null;
        }
    }

    /**
     * Get customer name from Facebook API.
     */
    private function getCustomerName(): ?string
    {
        try {
            $response = Http::get("https://graph.facebook.com/{$this->senderId}", [
                'fields' => 'name',
                'access_token' => $this->pageToken,
            ]);

            if ($response->successful()) {
                return $response->json('name');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch customer name', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function checkDataset(string $message, string $pageId): ?string
    {
        $filePath = resource_path("views/social/replies/{$pageId}.json");

        if (!file_exists($filePath)) {
            return null;
        }

        $dataset = json_decode(file_get_contents($filePath), true);
        if (!is_array($dataset)) {
            return null;
        }

        foreach ($dataset as $item) {
            if (strtolower($item['comment'] ?? '') === strtolower($message)) {
                return $item['reply'] ?? null;
            }
        }

        return null;
    }

    private function generateHFReply(string $userMessage): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('HUGGING_FACE_TOKEN'),
        ])->post('https://api-inference.huggingface.co/models/gpt2', [
            'inputs' => "Reply to this message politely: \"{$userMessage}\"",
        ]);

        $body = $response->json();

        return $body[0]['generated_text'] ?? 'Thanks for your message!';
    }
}

