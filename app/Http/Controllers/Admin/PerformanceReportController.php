<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Models\SupportMessage;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PerformanceReportController extends Controller
{
    public function agentPerformance(Request $request): JsonResponse|View
    {
        $filters = $this->validatedFilters($request);

        $agents = $this->agentQuery($filters['agent_id'])
            ->orderBy('name')
            ->get();

        $reports = $agents
            ->map(fn (User $agent) => $this->buildAgentReport($agent, $filters))
            ->values();

        $summary = [
            'agents_count' => $reports->count(),
            'assigned_tickets_count' => $reports->sum('assigned_tickets_count'),
            'agent_replies_count' => $reports->sum('agent_replies_count'),
            'connected_pages_count' => $this->connectedPagesCountForAgents($agents->pluck('id')),
            'response_samples_count' => $reports->sum('response_times.samples_count'),
            'average_reply_time_seconds' => $this->weightedAverageReplySeconds($reports),
        ];
        $summary['average_reply_time_human'] = $this->formatDuration($summary['average_reply_time_seconds']);

        $payload = [
            'filters' => [
                'from' => $filters['from']?->toDateString(),
                'to' => $filters['to']?->toDateString(),
                'agent_id' => $filters['agent_id'],
            ],
            'summary' => $summary,
            'agents' => $reports,
        ];

        if ($request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json($payload);
        }

        $agentOptions = $this->agentQuery(null)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.reports.agent-performance', [
            'reports' => $reports,
            'summary' => $summary,
            'filters' => $payload['filters'],
            'agentOptions' => $agentOptions,
        ]);
    }

    public function pagePerformance(Request $request): JsonResponse|View
    {
        $filters = $this->validatedFilters($request);

        $pages = $this->pageQuery($filters['page_id'])
            ->orderBy('page_name')
            ->get();

        $reports = $pages
            ->map(fn (FacebookPage $page) => $this->buildPageReport($page, $filters))
            ->values();

        $summary = [
            'pages_count' => $reports->count(),
            'tickets_count' => $reports->sum('tickets_count'),
            'customer_messages_count' => $reports->sum('customer_messages_count'),
            'agent_replies_count' => $reports->sum('agent_replies_count'),
            'connected_agents_count' => $this->connectedAgentsCountForPages($pages->pluck('id')),
            'response_samples_count' => $reports->sum('response_times.samples_count'),
            'average_reply_time_seconds' => $this->weightedAverageReplySeconds($reports),
        ];
        $summary['average_reply_time_human'] = $this->formatDuration($summary['average_reply_time_seconds']);

        $payload = [
            'filters' => [
                'from' => $filters['from']?->toDateString(),
                'to' => $filters['to']?->toDateString(),
                'page_id' => $filters['page_id'],
            ],
            'summary' => $summary,
            'pages' => $reports,
        ];

        if ($request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json($payload);
        }

        $pageOptions = $this->pageQuery(null)
            ->orderBy('page_name')
            ->get(['id', 'page_id', 'page_name']);

        return view('admin.reports.page-performance', [
            'reports' => $reports,
            'summary' => $summary,
            'filters' => $payload['filters'],
            'pageOptions' => $pageOptions,
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'page_id' => ['nullable', 'integer', 'exists:facebook_pages,id'],
        ]);

        return [
            'from' => isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null,
            'to' => isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null,
            'agent_id' => $validated['agent_id'] ?? null,
            'page_id' => $validated['page_id'] ?? null,
        ];
    }

    private function agentQuery(?int $agentId): Builder
    {
        return User::query()
            ->when($agentId, fn (Builder $query) => $query->where('id', $agentId))
            ->where(function (Builder $query) {
                $query->whereHas('roles', function (Builder $roles) {
                    $roles->whereIn('name', ['agent', 'admin', 'manager']);
                })
                    ->orWhereHas('tickets')
                    ->orWhereHas('supportMessages', function (Builder $messages) {
                        $messages->where('message_type', 'agent');
                    })
                    ->orWhereHas('supportQueues');
            });
    }

    private function buildAgentReport(User $agent, array $filters): array
    {
        $ticketsQuery = Ticket::query()->where('assigned_to', $agent->id);
        $this->applyDateRange($ticketsQuery, 'created_at', $filters);

        $statusCounts = (clone $ticketsQuery)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $replyQuery = $this->agentReplyQuery($agent);
        $this->applyDateRange($replyQuery, 'created_at', $filters);

        $firstReplyAt = (clone $replyQuery)->oldest('created_at')->value('created_at');
        $latestReplyAt = (clone $replyQuery)->latest('created_at')->value('created_at');
        $responseTimes = $this->responseTimeStats($agent, $filters);

        return [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'availability_status' => $agent->availability_status,
            ],
            'assigned_tickets_count' => (clone $ticketsQuery)->count(),
            'open_tickets_count' => (int) ($statusCounts['open'] ?? 0),
            'waiting_tickets_count' => (int) ($statusCounts['waiting'] ?? 0),
            'solved_tickets_count' => (int) ($statusCounts['solved'] ?? 0),
            'closed_tickets_count' => (int) ($statusCounts['closed'] ?? 0),
            'agent_replies_count' => (clone $replyQuery)->count(),
            'connected_pages_count' => $this->connectedPagesCount($agent),
            'handled_pages_count' => $this->handledPagesCount($agent, $filters),
            'first_reply_at' => $firstReplyAt ? Carbon::parse($firstReplyAt)->toDateTimeString() : null,
            'latest_reply_at' => $latestReplyAt ? Carbon::parse($latestReplyAt)->toDateTimeString() : null,
            'response_times' => $responseTimes,
        ];
    }

    private function pageQuery(?int $pageId): Builder
    {
        return FacebookPage::query()
            ->when($pageId, fn (Builder $query) => $query->where('id', $pageId))
            ->with(['supportQueues.users:id,name,email,availability_status']);
    }

    private function buildPageReport(FacebookPage $page, array $filters): array
    {
        $ticketsQuery = Ticket::query()->where('facebook_page_id', $page->page_id);
        $this->applyDateRange($ticketsQuery, 'created_at', $filters);

        $statusCounts = (clone $ticketsQuery)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $channelCounts = (clone $ticketsQuery)
            ->select('channel', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('channel')
            ->pluck('aggregate', 'channel');

        $customerMessagesQuery = $this->pageMessageQuery($page, 'customer');
        $this->applyDateRange($customerMessagesQuery, 'created_at', $filters);

        $agentRepliesQuery = $this->pageMessageQuery($page, 'agent');
        $this->applyDateRange($agentRepliesQuery, 'created_at', $filters);

        $connectedAgents = $page->supportQueues
            ->flatMap->users
            ->unique('id')
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'availability_status' => $user->availability_status,
            ]);

        $firstReplyAt = (clone $agentRepliesQuery)->oldest('created_at')->value('created_at');
        $latestReplyAt = (clone $agentRepliesQuery)->latest('created_at')->value('created_at');

        return [
            'page' => [
                'id' => $page->id,
                'page_id' => $page->page_id,
                'name' => $page->page_name,
                'category' => $page->page_category,
            ],
            'tickets_count' => (clone $ticketsQuery)->count(),
            'open_tickets_count' => (int) ($statusCounts['open'] ?? 0),
            'waiting_tickets_count' => (int) ($statusCounts['waiting'] ?? 0),
            'solved_tickets_count' => (int) ($statusCounts['solved'] ?? 0),
            'closed_tickets_count' => (int) ($statusCounts['closed'] ?? 0),
            'messenger_tickets_count' => (int) ($channelCounts['messenger'] ?? 0),
            'comment_tickets_count' => (int) ($channelCounts['comment'] ?? 0),
            'customer_messages_count' => (clone $customerMessagesQuery)->count(),
            'agent_replies_count' => (clone $agentRepliesQuery)->count(),
            'assigned_agents_count' => (clone $ticketsQuery)->whereNotNull('assigned_to')->distinct()->count('assigned_to'),
            'connected_agents_count' => $connectedAgents->count(),
            'connected_agents' => $connectedAgents,
            'support_queues' => $page->supportQueues
                ->map(fn ($queue) => [
                    'id' => $queue->id,
                    'name' => $queue->name,
                ])
                ->values(),
            'first_reply_at' => $firstReplyAt ? Carbon::parse($firstReplyAt)->toDateTimeString() : null,
            'latest_reply_at' => $latestReplyAt ? Carbon::parse($latestReplyAt)->toDateTimeString() : null,
            'response_times' => $this->responseTimeStatsForPage($page, $filters),
        ];
    }

    private function agentReplyQuery(User $agent): Builder
    {
        return SupportMessage::query()
            ->where('message_type', 'agent')
            ->where(function (Builder $query) use ($agent) {
                $query->where('user_id', $agent->id)
                    ->orWhere(function (Builder $legacy) use ($agent) {
                        $legacy->whereNull('user_id')
                            ->whereHas('ticket', fn (Builder $ticket) => $ticket->where('assigned_to', $agent->id));
                    });
            });
    }

    private function connectedPagesCount(User $agent): int
    {
        return DB::table('facebook_page_support_queue')
            ->join('support_queue_user', 'facebook_page_support_queue.support_queue_id', '=', 'support_queue_user.support_queue_id')
            ->where('support_queue_user.user_id', $agent->id)
            ->distinct()
            ->count('facebook_page_support_queue.facebook_page_id');
    }

    private function connectedPagesCountForAgents(Collection $agentIds): int
    {
        if ($agentIds->isEmpty()) {
            return 0;
        }

        return DB::table('facebook_page_support_queue')
            ->join('support_queue_user', 'facebook_page_support_queue.support_queue_id', '=', 'support_queue_user.support_queue_id')
            ->whereIn('support_queue_user.user_id', $agentIds)
            ->distinct()
            ->count('facebook_page_support_queue.facebook_page_id');
    }

    private function handledPagesCount(User $agent, array $filters): int
    {
        $query = Ticket::query()
            ->where('assigned_to', $agent->id)
            ->whereNotNull('facebook_page_id');

        $this->applyDateRange($query, 'created_at', $filters);

        return $query->distinct()->count('facebook_page_id');
    }

    private function pageMessageQuery(FacebookPage $page, string $messageType): Builder
    {
        return SupportMessage::query()
            ->where('message_type', $messageType)
            ->whereHas('ticket', fn (Builder $ticket) => $ticket->where('facebook_page_id', $page->page_id));
    }

    private function connectedAgentsCountForPages(Collection $pageIds): int
    {
        if ($pageIds->isEmpty()) {
            return 0;
        }

        return DB::table('facebook_page_support_queue')
            ->join('support_queue_user', 'facebook_page_support_queue.support_queue_id', '=', 'support_queue_user.support_queue_id')
            ->whereIn('facebook_page_support_queue.facebook_page_id', $pageIds)
            ->distinct()
            ->count('support_queue_user.user_id');
    }

    private function responseTimeStats(User $agent, array $filters): array
    {
        $replyQuery = $this->agentReplyQuery($agent);
        $this->applyDateRange($replyQuery, 'created_at', $filters);

        $ticketIds = $replyQuery
            ->select('ticket_id')
            ->distinct()
            ->pluck('ticket_id');

        if ($ticketIds->isEmpty()) {
            return $this->emptyResponseTimeStats();
        }

        $ticketAssignments = Ticket::query()
            ->whereIn('id', $ticketIds)
            ->pluck('assigned_to', 'id');

        $messagesQuery = SupportMessage::query()
            ->whereIn('ticket_id', $ticketIds)
            ->whereIn('message_type', ['customer', 'agent']);

        if ($filters['to']) {
            $messagesQuery->where('created_at', '<=', $filters['to']);
        }

        $messages = $messagesQuery
            ->orderBy('ticket_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'ticket_id', 'user_id', 'message_type', 'created_at']);

        $durations = [];
        $pendingCustomerAt = [];

        foreach ($messages as $message) {
            if ($message->message_type === 'customer') {
                $pendingCustomerAt[$message->ticket_id] ??= $message->created_at;
                continue;
            }

            if ($message->message_type !== 'agent' || ! isset($pendingCustomerAt[$message->ticket_id])) {
                continue;
            }

            $replyAgentId = $message->user_id ?: ($ticketAssignments[$message->ticket_id] ?? null);
            $isInsideReportRange = (! $filters['from'] || $message->created_at->gte($filters['from']))
                && (! $filters['to'] || $message->created_at->lte($filters['to']));

            if ((int) $replyAgentId === $agent->id && $isInsideReportRange) {
                $durations[] = $pendingCustomerAt[$message->ticket_id]->diffInSeconds($message->created_at);
            }

            unset($pendingCustomerAt[$message->ticket_id]);
        }

        if ($durations === []) {
            return $this->emptyResponseTimeStats();
        }

        $average = (int) round(array_sum($durations) / count($durations));
        $fastest = min($durations);
        $longest = max($durations);

        return [
            'samples_count' => count($durations),
            'average_seconds' => $average,
            'average_human' => $this->formatDuration($average),
            'fastest_seconds' => $fastest,
            'fastest_human' => $this->formatDuration($fastest),
            'longest_seconds' => $longest,
            'longest_human' => $this->formatDuration($longest),
        ];
    }

    private function responseTimeStatsForPage(FacebookPage $page, array $filters): array
    {
        $replyQuery = $this->pageMessageQuery($page, 'agent');
        $this->applyDateRange($replyQuery, 'created_at', $filters);

        $ticketIds = $replyQuery
            ->select('ticket_id')
            ->distinct()
            ->pluck('ticket_id');

        if ($ticketIds->isEmpty()) {
            return $this->emptyResponseTimeStats();
        }

        $messagesQuery = SupportMessage::query()
            ->whereIn('ticket_id', $ticketIds)
            ->whereIn('message_type', ['customer', 'agent']);

        if ($filters['to']) {
            $messagesQuery->where('created_at', '<=', $filters['to']);
        }

        $messages = $messagesQuery
            ->orderBy('ticket_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'ticket_id', 'message_type', 'created_at']);

        $durations = [];
        $pendingCustomerAt = [];

        foreach ($messages as $message) {
            if ($message->message_type === 'customer') {
                $pendingCustomerAt[$message->ticket_id] ??= $message->created_at;
                continue;
            }

            if ($message->message_type !== 'agent' || ! isset($pendingCustomerAt[$message->ticket_id])) {
                continue;
            }

            $isInsideReportRange = (! $filters['from'] || $message->created_at->gte($filters['from']))
                && (! $filters['to'] || $message->created_at->lte($filters['to']));

            if ($isInsideReportRange) {
                $durations[] = $pendingCustomerAt[$message->ticket_id]->diffInSeconds($message->created_at);
            }

            unset($pendingCustomerAt[$message->ticket_id]);
        }

        if ($durations === []) {
            return $this->emptyResponseTimeStats();
        }

        $average = (int) round(array_sum($durations) / count($durations));
        $fastest = min($durations);
        $longest = max($durations);

        return [
            'samples_count' => count($durations),
            'average_seconds' => $average,
            'average_human' => $this->formatDuration($average),
            'fastest_seconds' => $fastest,
            'fastest_human' => $this->formatDuration($fastest),
            'longest_seconds' => $longest,
            'longest_human' => $this->formatDuration($longest),
        ];
    }

    private function emptyResponseTimeStats(): array
    {
        return [
            'samples_count' => 0,
            'average_seconds' => null,
            'average_human' => 'N/A',
            'fastest_seconds' => null,
            'fastest_human' => 'N/A',
            'longest_seconds' => null,
            'longest_human' => 'N/A',
        ];
    }

    private function weightedAverageReplySeconds(Collection $reports): ?int
    {
        $samples = $reports->sum('response_times.samples_count');

        if ($samples === 0) {
            return null;
        }

        $weightedTotal = $reports->sum(function (array $report) {
            return ($report['response_times']['average_seconds'] ?? 0)
                * $report['response_times']['samples_count'];
        });

        return (int) round($weightedTotal / $samples);
    }

    private function applyDateRange(Builder $query, string $column, array $filters): void
    {
        if ($filters['from']) {
            $query->where($column, '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->where($column, '<=', $filters['to']);
        }
    }

    private function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return 'N/A';
        }

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return trim("{$hours}h {$minutes}m");
        }

        return $remainingSeconds > 0
            ? "{$minutes}m {$remainingSeconds}s"
            : "{$minutes}m";
    }
}
