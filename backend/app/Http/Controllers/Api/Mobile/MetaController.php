<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Support\MobileApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends MobileController
{
    public function show(Request $request): JsonResponse
    {
        return MobileApi::success($request, [
            'api_version' => 'mobile-v1',
            'app_name' => config('app.name', 'MobileANTRIAN'),
            'min_supported_app_version' => config('mobile.min_supported_app_version', '1.0.0'),
            'server_time' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ]);
    }
}
