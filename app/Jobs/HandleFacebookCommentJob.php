<?php

namespace App\Jobs;

use App\Events\NewTicketMessage;
use App\Models\Ticket;
use App\Services\TicketAssignmentService;
use App\Services\TicketLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleFacebookCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public string $pageId;
    public string $pageToken;
    public ?string $commentId;
    public string $message;
    public ?string $fromId;
    public ?string $postId;
    private bool $createdNewTicket = false;

    public function __construct(string $pageId, string $pageToken, ?string $commentId, string $message, ?string $fromId, ?string $postId = null)
    {
        $this->pageId = $pageId;
        $this->pageToken = $pageToken;
        $this->commentId = $commentId;
        $this->message = $message;
        $this->fromId = $fromId;
        $this->postId = $postId;
    }

    public function handle(): void
    {
        if (empty($this->pageToken) || empty($this->commentId)) {
            Log::warning('HandleFacebookCommentJob skipped because required data is missing.', [
                'page_id' => $this->pageId,
                'comment_id' => $this->commentId,
            ]);
            return;
        }

        if ($this->fromId === $this->pageId) {
            return;
        }

        // Get or create ticket for this customer (for comments)
        $ticket = $this->getOrCreateTicket();

        if ($ticket) {
            app(TicketAssignmentService::class)->assignTicketFromPageQueue($ticket);

            // Add message to ticket
            $commentMessage = $ticket->addMessage(
                facebookMessageId: $this->commentId,
                senderFacebookId: $this->fromId,
                message: $this->message,
                messageType: 'customer',
                channel: 'comment'
            );

            // Broadcast the new message event via Reverb for real-time updates
            event(new NewTicketMessage($ticket, $commentMessage, $this->createdNewTicket));

            // Notify the assigned agent about the new message
            if ($ticket->assigned_to && $ticket->assignedAgent) {
                $ticket->assignedAgent->notify(new \App\Notifications\NewTicketMessage($ticket, $commentMessage));
            }
        }

        // Send automatic reply
        $reply = $this->checkDataset($this->message, $this->pageId)
            ?? $this->generateHFReply($this->message);

        $response = Http::post("https://graph.facebook.com/v25.0/{$this->commentId}/comments", [
            'message' => $reply,
            'access_token' => $this->pageToken,
        ]);

        Log::info('HandleFacebookCommentJob response', [
            'page_id' => $this->pageId,
            'comment_id' => $this->commentId,
            'ticket_id' => $ticket?->id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }

    /**
     * Get existing ticket or create a new one for this customer (comment).
     * Each comment creates its own ticket since they're separate conversations.
     */
    private function getOrCreateTicket(): ?Ticket
    {
        try {
            $ticket = null;

            if (!empty($this->commentId)) {
                $ticket = Ticket::where('facebook_comment_id', $this->commentId)
                    ->where('channel', 'comment')
                    ->first();
            }

            if (!$ticket && !empty($this->postId) && !empty($this->fromId)) {
                $ticket = Ticket::where('facebook_post_id', $this->postId)
                    ->where('customer_facebook_id', $this->fromId)
                    ->where('channel', 'comment')
                    ->first();
            }

            if ($ticket) {
                if ($ticket->status === 'closed') {
                    $ticket = null;
                } elseif ($ticket->status === 'solved') {
                    $ticket->update(['status' => 'open']);
                    $this->createdNewTicket = false;
                    return $ticket;
                } else {
                    $this->createdNewTicket = false;
                    return $ticket;
                }
            }

            $ticket = Ticket::create([
                'facebook_page_id' => $this->pageId,
                'customer_facebook_id' => $this->fromId,
                'customer_name' => $this->getCustomerName(),
                'subject' => $this->buildCommentSubject(),
                'channel' => 'comment',
                'facebook_post_id' => $this->postId,
                'facebook_comment_id' => $this->commentId,
                'initial_message' => $this->message,
                'status' => 'open',
                'priority' => 'medium',
            ]);

            $this->createdNewTicket = true;

            TicketLogService::logAction(
                $ticket,
                'created',
                description: 'Ticket created automatically from customer comment'
            );

            Log::info('New support ticket created from comment', [
                'ticket_id' => $ticket->id,
                'customer_id' => $this->fromId,
                'page_id' => $this->pageId,
                'comment_id' => $this->commentId,
            ]);

            return $ticket;
        } catch (\Exception $e) {
            Log::error('Error creating ticket from comment', [
                'error' => $e->getMessage(),
                'page_id' => $this->pageId,
                'from_id' => $this->fromId,
                'comment_id' => $this->commentId,
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
            $response = Http::get("https://graph.facebook.com/{$this->fromId}", [
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

    private function buildCommentSubject(): string
    {
        if ($this->postId) {
            return 'Comment on post ' . $this->shortenPostId();
        }

        return 'Comment Support Request - ' . now()->format('Y-m-d H:i');
    }

    private function shortenPostId(): string
    {
        return strlen($this->postId ?? '') > 18 ? substr($this->postId, -18) : ($this->postId ?? 'unknown');
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
            'inputs' => "Reply to this comment politely: \"{$userMessage}\"",
        ]);

        $body = $response->json();

        return $body[0]['generated_text'] ?? 'Thanks for your comment!';
    }
}

