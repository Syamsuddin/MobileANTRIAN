<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Services\Mobile\OperatorStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorStateController extends MobileController
{
    public function show(Request $request, OperatorStateService $stateService): JsonResponse
    {
        return $this->success($request, $stateService->state($request->attributes->get('mobile_user')));
    }
}
