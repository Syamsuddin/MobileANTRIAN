<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\MobileApiException;
use App\Models\Ticket;
use App\Services\Mobile\OperatorStateService;
use App\Services\Queue\CallNextTicketAction;
use App\Services\Queue\CompleteTicketAction;
use App\Services\Queue\RecallTicketAction;
use App\Services\Queue\SkipTicketAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperatorQueueController extends MobileController
{
    public function callNext(Request $request, OperatorStateService $stateService, CallNextTicketAction $action): JsonResponse
    {
        return $this->runQueueAction($request, $stateService, function () use ($request, $stateService, $action) {
            $assignment = $stateService->requireAssignment($request->attributes->get('mobile_user'));
            $action->execute($assignment, $request);
        });
    }

    public function recall(Request $request, Ticket $ticket, OperatorStateService $stateService, RecallTicketAction $action): JsonResponse
    {
        return $this->runQueueAction($request, $stateService, function () use ($request, $ticket, $stateService, $action) {
            $assignment = $stateService->requireAssignment($request->attributes->get('mobile_user'));
            $action->execute($assignment, $ticket, $request);
        });
    }

    public function skip(Request $request, Ticket $ticket, OperatorStateService $stateService, SkipTicketAction $action): JsonResponse
    {
        try {
            $payload = $this->validatePayload($request, ['reason' => ['nullable', 'string', 'max:255']]);
        } catch (ValidationException $exception) {
            return $this->validationError($request, $exception);
        }

        return $this->runQueueAction($request, $stateService, function () use ($request, $ticket, $stateService, $action, $payload) {
            $assignment = $stateService->requireAssignment($request->attributes->get('mobile_user'));
            $action->execute($assignment, $ticket, $payload['reason'] ?? null, $request);
        });
    }

    public function done(Request $request, Ticket $ticket, OperatorStateService $stateService, CompleteTicketAction $action): JsonResponse
    {
        try {
            $payload = $this->validatePayload($request, ['notes' => ['nullable', 'string', 'max:255']]);
        } catch (ValidationException $exception) {
            return $this->validationError($request, $exception);
        }

        return $this->runQueueAction($request, $stateService, function () use ($request, $ticket, $stateService, $action, $payload) {
            $assignment = $stateService->requireAssignment($request->attributes->get('mobile_user'));
            $action->execute($assignment, $ticket, $payload['notes'] ?? null, $request);
        });
    }

    private function runQueueAction(Request $request, OperatorStateService $stateService, callable $callback): JsonResponse
    {
        $idempotencyKey = $request->headers->get('Idempotency-Key');
        $operator = $request->attributes->get('mobile_user');
        $cacheKey = $idempotencyKey ? 'mobile-idempotency:'.$operator->id.':'.$request->path().':'.$idempotencyKey : null;

        if ($cacheKey && Cache::has($cacheKey)) {
            return $this->success($request, $stateService->state($operator) + ['idempotent_replay' => true]);
        }

        try {
            $callback();
            if ($cacheKey) {
                Cache::put($cacheKey, true, now()->addSeconds(60));
            }

            return $this->success($request, $stateService->state($operator));
        } catch (MobileApiException $exception) {
            return $this->fail($request, $exception);
        } catch (Throwable $exception) {
            return $this->fail($request, $exception);
        }
    }
}
