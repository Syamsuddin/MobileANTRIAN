<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\MobileApiException;
use App\Http\Controllers\Controller;
use App\Support\MobileApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class MobileController extends Controller
{
    protected function success(Request $request, array $data = [], int $status = 200): JsonResponse
    {
        return MobileApi::success($request, $data, $status);
    }

    protected function fail(Request $request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof MobileApiException) {
            return MobileApi::error(
                $request,
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->details
            );
        }

        report($exception);

        return MobileApi::error($request, 'SERVER_ERROR', 'Terjadi kesalahan server.', 500);
    }

    protected function validatePayload(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function validationError(Request $request, ValidationException $exception): JsonResponse
    {
        return MobileApi::error(
            $request,
            'VALIDATION_FAILED',
            'Payload tidak valid.',
            422,
            $exception->errors()
        );
    }
}
