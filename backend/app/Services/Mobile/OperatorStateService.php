<?php

namespace App\Services\Mobile;

use App\Exceptions\MobileApiException;
use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OperatorStateService
{
    public function assignmentFor(User $operator): ?CounterAssignment
    {
        return CounterAssignment::query()
            ->with(['counter.services' => fn ($query) => $query->where('services.is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->where('user_id', $operator->id)
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function requireAssignment(User $operator): CounterAssignment
    {
        $assignment = $this->assignmentFor($operator);

        if (! $assignment || ! $assignment->counter || ! $assignment->counter->is_active) {
            throw new MobileApiException('ASSIGNMENT_REQUIRED', 'Akun Anda belum memiliki loket aktif. Hubungi admin.', 403);
        }

        return $assignment;
    }

    public function state(User $operator): array
    {
        $assignment = $this->assignmentFor($operator);

        if (! $assignment || ! $assignment->counter || ! $assignment->counter->is_active) {
            return [
                'assignment' => null,
                'active_ticket' => null,
                'waiting' => [],
                'summary' => [
                    'waiting_total' => 0,
                    'served_today' => 0,
                    'skipped_today' => 0,
                ],
            ];
        }

        $counter = $assignment->counter;
        $services = $counter->services;
        $serviceIds = $services->pluck('id')->values();
        $today = now()->toDateString();
        $activeTicket = $this->activeTicketForCounter($counter->id);
        $waiting = $this->waitingTickets($serviceIds, $today, 20);

        return [
            'assignment' => [
                'id' => $assignment->id,
                'counter' => [
                    'id' => $counter->id,
                    'code' => $counter->code,
                    'name' => $counter->name,
                    'location' => $counter->location,
                ],
                'services' => $services->map(fn ($service) => [
                    'id' => $service->id,
                    'code' => $service->code,
                    'name' => $service->name,
                    'prefix' => $service->prefix,
                    'color' => $service->color,
                ])->values()->all(),
            ],
            'active_ticket' => $activeTicket ? $this->ticketPayload($activeTicket) : null,
            'waiting' => $waiting->map(fn (Ticket $ticket) => $this->waitingPayload($ticket))->values()->all(),
            'summary' => [
                'waiting_total' => Ticket::query()
                    ->whereIn('service_id', $serviceIds)
                    ->whereDate('ticket_date', $today)
                    ->where('status', Ticket::STATUS_WAITING)
                    ->count(),
                'served_today' => QueueCall::query()
                    ->where('counter_id', $counter->id)
                    ->where('event_type', QueueCall::EVENT_DONE)
                    ->whereDate('called_at', $today)
                    ->count(),
                'skipped_today' => QueueCall::query()
                    ->where('counter_id', $counter->id)
                    ->where('event_type', QueueCall::EVENT_SKIP)
                    ->whereDate('called_at', $today)
                    ->count(),
            ],
        ];
    }

    public function activeTicketForCounter(int $counterId): ?Ticket
    {
        return Ticket::query()
            ->with('service')
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->whereHas('queueCalls', fn ($query) => $query->where('counter_id', $counterId))
            ->latest('called_at')
            ->first();
    }

    public function assertTicketActiveForCounter(Ticket $ticket, int $counterId): void
    {
        $belongsToCounter = $ticket->queueCalls()
            ->where('counter_id', $counterId)
            ->whereIn('event_type', [QueueCall::EVENT_CALL, QueueCall::EVENT_RECALL])
            ->exists();

        if (! in_array($ticket->status, Ticket::ACTIVE_STATUSES, true) || ! $belongsToCounter) {
            throw new MobileApiException(
                'TICKET_NOT_ACTIVE_FOR_COUNTER',
                'Nomor ini tidak aktif untuk loket Anda.',
                422
            );
        }
    }

    private function waitingTickets(Collection $serviceIds, string $today, int $limit): Collection
    {
        if ($serviceIds->isEmpty()) {
            return collect();
        }

        return Ticket::query()
            ->with('service')
            ->whereIn('service_id', $serviceIds)
            ->whereDate('ticket_date', $today)
            ->where('status', Ticket::STATUS_WAITING)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function ticketPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'service_name' => $ticket->service?->name,
            'status' => $ticket->status,
            'called_at' => $this->iso($ticket->called_at),
            'started_at' => $this->iso($ticket->started_at),
            'duration_seconds' => $ticket->called_at ? (int) $ticket->called_at->diffInSeconds(now()) : null,
        ];
    }

    private function waitingPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'service_name' => $ticket->service?->name,
            'created_at' => $this->iso($ticket->created_at),
            'waiting_seconds' => (int) $ticket->created_at->diffInSeconds(now()),
        ];
    }

    private function iso(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }
}
