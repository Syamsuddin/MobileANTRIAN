<?php

namespace App\Services\Queue;

use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Services\Mobile\OperatorStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecallTicketAction extends QueueAction
{
    public function execute(CounterAssignment $assignment, Ticket $ticket, Request $request): Ticket
    {
        return DB::transaction(function () use ($assignment, $ticket, $request): Ticket {
            $ticket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            app(OperatorStateService::class)->assertTicketActiveForCounter($ticket, $assignment->counter_id);

            $this->createCall($ticket, $assignment, QueueCall::EVENT_RECALL, null, $request);
            $this->audit($assignment, $ticket, 'queue.recall', $request);

            return $ticket->refresh()->load('service');
        });
    }
}
