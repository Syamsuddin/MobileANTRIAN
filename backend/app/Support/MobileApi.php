<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileApi
{
    public static function requestId(Request $request): string
    {
        $requestId = $request->headers->get('X-Request-ID') ?: $request->attributes->get('request_id');

        if (! $requestId) {
            $requestId = (string) str()->uuid();
            $request->attributes->set('request_id', $requestId);
        }

        return $requestId;
    }

    public static function success(Request $request, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'request_id' => self::requestId($request),
            'server_time' => now()->toIso8601String(),
            'data' => $data,
        ], $status);
    }

    public static function error(
        Request $request,
        string $code,
        string $message,
        int $status,
        array $details = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'request_id' => self::requestId($request),
            'server_time' => now()->toIso8601String(),
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
