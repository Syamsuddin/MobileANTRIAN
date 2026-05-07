<?php

namespace App\Services\Queue;

use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Services\Mobile\OperatorStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompleteTicketAction extends QueueAction
{
    public function execute(CounterAssignment $assignment, Ticket $ticket, ?string $notes, Request $request): Ticket
    {
        return DB::transaction(function () use ($assignment, $ticket, $notes, $request): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            app(OperatorStateService::class)->assertTicketActiveForCounter($ticket, $assignment->counter_id);

            $ticket->forceFill([
                'status' => Ticket::STATUS_DONE,
                'completed_at' => now(),
            ])->save();

            $this->createCall($ticket, $assignment, QueueCall::EVENT_DONE, $notes, $request);
            $this->audit($assignment, $ticket, 'queue.done', $request, ['notes' => $notes]);

            return $ticket->refresh()->load('service');
        });
    }
}
