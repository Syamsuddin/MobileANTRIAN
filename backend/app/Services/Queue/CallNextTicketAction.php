<?php

namespace App\Services\Queue;

use App\Exceptions\MobileApiException;
use App\Models\CounterAssignment;
use App\Models\QueueCall;
use App\Models\Ticket;
use App\Services\Mobile\OperatorStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallNextTicketAction extends QueueAction
{
    public function execute(CounterAssignment $assignment, Request $request): Ticket
    {
        return DB::transaction(function () use ($assignment, $request): Ticket {
            if ($assignment->counter->services->isEmpty()) {
                throw new MobileApiException('ASSIGNMENT_REQUIRED', 'Loket belum memiliki layanan aktif.', 403);
            }

            $activeTicket = app(OperatorStateService::class)->activeTicketForCounter($assignment->counter_id);
            if ($activeTicket) {
                throw new MobileApiException(
                    'ACTIVE_TICKET_EXISTS',
                    'Selesaikan atau skip nomor aktif sebelum memanggil berikutnya.',
                    409
                );
            }

            $serviceIds = $assignment->counter->services->pluck('id');
            $ticket = Ticket::query()
                ->whereIn('service_id', $serviceIds)
                ->whereDate('ticket_date', now()->toDateString())
                ->where('status', Ticket::STATUS_WAITING)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new MobileApiException('QUEUE_EMPTY', 'Belum ada antrian menunggu.', 409);
            }

            $ticket->forceFill([
                'status' => Ticket::STATUS_SERVING,
                'called_at' => now(),
                'started_at' => now(),
            ])->save();

            $this->createCall($ticket, $assignment, QueueCall::EVENT_CALL, null, $request);
            $this->audit($assignment, $ticket, 'queue.call', $request);

            return $ticket->refresh()->load('service');
        });
    }
}
