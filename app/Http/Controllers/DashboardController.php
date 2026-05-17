<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\RagChunk;
use App\Models\RagDocument;
use App\Models\SupportMessage;
use App\Models\SupportQueue;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $lastSevenDays = now()->subDays(6)->startOfDay();

        $pagesCount = FacebookPage::count();
        $ticketsCount = Ticket::count();
        $openTicketsCount = Ticket::where('status', 'open')->count();
        $waitingTicketsCount = Ticket::where('status', 'waiting')->count();
        $solvedTicketsCount = Ticket::where('status', 'solved')->count();
        $closedTicketsCount = Ticket::where('status', 'closed')->count();
        $activeTicketsCount = Ticket::whereIn('status', ['open', 'waiting'])->count();
        $todayTicketsCount = Ticket::where('created_at', '>=', $today)->count();
        $todayMessagesCount = SupportMessage::where('created_at', '>=', $today)->count();
        $unreadCustomerMessagesCount = SupportMessage::where('message_type', 'customer')
            ->where('is_read', false)
            ->count();
        $messengerTicketsCount = Ticket::where('channel', 'messenger')->count();
        $commentTicketsCount = Ticket::where('channel', 'comment')->count();
        $supportQueuesCount = SupportQueue::count();
        $agentsCount = User::where(function (Builder $query): void {
            $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['agent', 'admin', 'manager']))
                ->orWhereHas('tickets')
                ->orWhereHas('supportQueues');
        })->count();
        $availableAgentsCount = User::where('availability_status', User::STATUS_ONLINE)->count();

        $statusCounts = [
            'open' => $openTicketsCount,
            'waiting' => $waitingTicketsCount,
            'solved' => $solvedTicketsCount,
            'closed' => $closedTicketsCount,
        ];

        $recentTickets = Ticket::with(['assignedAgent', 'latestMessage'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Ticket $ticket) {
                return [
                    'id' => $ticket->id,
                    'customer_name' => $ticket->customer_name ?: $ticket->customer_facebook_id,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'channel' => $ticket->channel,
                    'agent_name' => $ticket->assignedAgent?->name,
                    'latest_message' => $ticket->latestMessage?->message ?: $ticket->initial_message,
                    'updated_at' => $ticket->updated_at,
                    'page_name' => $this->pageNameForTicket($ticket),
                ];
            });

        $topPages = FacebookPage::query()
            ->orderBy('page_name')
            ->get()
            ->map(function (FacebookPage $page) {
                $tickets = Ticket::query()
                    ->where(function (Builder $query) use ($page): void {
                        $query->where('facebook_page_id', $page->id)
                            ->orWhere('facebook_page_id', $page->page_id);
                    });

                return [
                    'id' => $page->id,
                    'name' => $page->page_name,
                    'category' => $page->page_category,
                    'tickets_count' => (clone $tickets)->count(),
                    'active_count' => (clone $tickets)->whereIn('status', ['open', 'waiting'])->count(),
                    'comment_count' => (clone $tickets)->where('channel', 'comment')->count(),
                    'messenger_count' => (clone $tickets)->where('channel', 'messenger')->count(),
                ];
            })
            ->sortByDesc('tickets_count')
            ->take(5)
            ->values();

        $agentWorkload = User::query()
            ->where(function (Builder $query): void {
                $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['agent', 'admin', 'manager']))
                    ->orWhereHas('tickets');
            })
            ->withCount([
                'tickets as active_tickets_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'waiting']),
                'tickets as solved_tickets_count' => fn (Builder $query) => $query->where('status', 'solved'),
            ])
            ->orderByDesc('active_tickets_count')
            ->limit(5)
            ->get(['id', 'name', 'email', 'availability_status']);

        $ticketTrend = Ticket::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $lastSevenDays)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $lastSevenDayTrend = collect(range(0, 6))->map(function (int $offset) use ($ticketTrend) {
            $date = now()->subDays(6 - $offset)->toDateString();

            return [
                'label' => Carbon::parse($date)->format('M d'),
                'total' => (int) ($ticketTrend[$date] ?? 0),
            ];
        });

        $ragSummary = [
            'documents_count' => class_exists(RagDocument::class) ? RagDocument::count() : 0,
            'embedded_documents_count' => class_exists(RagDocument::class) ? RagDocument::where('status', RagDocument::STATUS_EMBEDDED)->count() : 0,
            'chunks_count' => class_exists(RagChunk::class) ? RagChunk::count() : 0,
        ];

        return view('dashboard',[
            'connected' => $pagesCount > 0,
            'pagesCount' => $pagesCount,
            'ticketsCount' => $ticketsCount,
            'openTicketsCount' => $openTicketsCount,
            'waitingTicketsCount' => $waitingTicketsCount,
            'solvedTicketsCount' => $solvedTicketsCount,
            'closedTicketsCount' => $closedTicketsCount,
            'activeTicketsCount' => $activeTicketsCount,
            'todayTicketsCount' => $todayTicketsCount,
            'todayMessagesCount' => $todayMessagesCount,
            'unreadCustomerMessagesCount' => $unreadCustomerMessagesCount,
            'messengerTicketsCount' => $messengerTicketsCount,
            'commentTicketsCount' => $commentTicketsCount,
            'supportQueuesCount' => $supportQueuesCount,
            'agentsCount' => $agentsCount,
            'availableAgentsCount' => $availableAgentsCount,
            'statusCounts' => $statusCounts,
            'recentTickets' => $recentTickets,
            'topPages' => $topPages,
            'agentWorkload' => $agentWorkload,
            'lastSevenDayTrend' => $lastSevenDayTrend,
            'ragSummary' => $ragSummary,
        ]);

    }

    private function pageNameForTicket(Ticket $ticket): string
    {
        $page = FacebookPage::query()
            ->where('id', $ticket->facebook_page_id)
            ->orWhere('page_id', $ticket->facebook_page_id)
            ->first();

        return $page?->page_name ?? 'Unknown page';
    }
}
