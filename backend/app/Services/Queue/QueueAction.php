<?php

namespace App\Services\Queue;

use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;

abstract class QueueAction
{
    public function __construct(protected AuditLogger $auditLogger) {}

    protected function createCall(
        Ticket $ticket,
        CounterAssignment $assignment,
        string $eventType,
        ?string $notes,
        Request $request
    ): QueueCall {
        $callNo = QueueCall::query()
            ->where('ticket_id', $ticket->id)
            ->where('counter_id', $assignment->counter_id)
            ->count() + 1;

        return QueueCall::create([
            'ticket_id' => $ticket->id,
            'counter_id' => $assignment->counter_id,
            'operator_id' => $assignment->user_id,
            'call_no' => $callNo,
            'event_type' => $eventType,
            'called_at' => now(),
            'notes' => $notes,
            'metadata' => $this->metadata($request),
        ]);
    }

    protected function audit(
        CounterAssignment $assignment,
        Ticket $ticket,
        string $action,
        Request $request,
        array $extra = []
    ): void {
        $this->auditLogger->log(
            $assignment->user,
            $action,
            Ticket::class,
            $ticket->id,
            array_merge($this->metadata($request), [
                'counter_id' => $assignment->counter_id,
                'ticket_no' => $ticket->ticket_no,
            ], $extra),
            $request->attributes->get('request_id')
        );
    }

    protected function metadata(Request $request): array
    {
        return [
            'request_id' => $request->attributes->get('request_id'),
            'app_version' => $request->headers->get('X-App-Version'),
            'platform' => $request->headers->get('X-Platform'),
            'installation_id' => $request->headers->get('X-Installation-ID'),
        ];
    }
}
