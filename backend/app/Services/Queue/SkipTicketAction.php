<?php

namespace App\Services\Queue;

use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Services\Mobile\OperatorStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkipTicketAction extends QueueAction
{
    public function execute(CounterAssignment $assignment, Ticket $ticket, ?string $reason, Request $request): Ticket
    {
        return DB::transaction(function () use ($assignment, $ticket, $reason, $request): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            app(OperatorStateService::class)->assertTicketActiveForCounter($ticket, $assignment->counter_id);

            $ticket->forceFill([
                'status' => Ticket::STATUS_SKIPPED,
                'skipped_at' => now(),
            ])->save();

            $this->createCall($ticket, $assignment, QueueCall::EVENT_SKIP, $reason, $request);
            $this->audit($assignment, $ticket, 'queue.skip', $request, ['reason' => $reason]);

            return $ticket->refresh()->load('service');
        });
    }
}
