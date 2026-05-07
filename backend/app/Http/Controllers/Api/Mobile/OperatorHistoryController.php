<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\QueueCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperatorHistoryController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $payload = $this->validatePayload($request, [
                'date' => ['nullable', 'date_format:Y-m-d'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);
        } catch (ValidationException $exception) {
            return $this->validationError($request, $exception);
        }

        $user = $request->attributes->get('mobile_user');
        $date = $payload['date'] ?? now()->toDateString();
        $limit = (int) ($payload['limit'] ?? 50);

        $events = QueueCall::query()
            ->with(['ticket.service', 'counter'])
            ->where('operator_id', $user->id)
            ->whereDate('called_at', $date)
            ->latest('called_at')
            ->limit($limit)
            ->get()
            ->map(fn (QueueCall $call) => [
                'id' => $call->id,
                'event_type' => $call->event_type,
                'ticket_no' => $call->ticket?->ticket_no,
                'service_name' => $call->ticket?->service?->name,
                'counter_name' => $call->counter?->name,
                'called_at' => $call->called_at->toIso8601String(),
                'notes' => $call->notes,
            ])
            ->values()
            ->all();

        return $this->success($request, ['events' => $events]);
    }
}
