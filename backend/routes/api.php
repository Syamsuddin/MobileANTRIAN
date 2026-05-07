<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\MetaController;
use App\Http\Controllers\Api\Mobile\OperatorHistoryController;
use App\Http\Controllers\Api\Mobile\OperatorQueueController;
use App\Http\Controllers\Api\Mobile\OperatorStateController;
use App\Http\Middleware\MobileBearerToken;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function () {
    Route::get('/meta', [MetaController::class, 'show']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(MobileBearerToken::class)->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/operator/state', [OperatorStateController::class, 'show']);
        Route::get('/operator/history', [OperatorHistoryController::class, 'index']);
        Route::post('/operator/queue/call-next', [OperatorQueueController::class, 'callNext']);
        Route::post('/operator/queue/{ticket}/recall', [OperatorQueueController::class, 'recall']);
        Route::post('/operator/queue/{ticket}/skip', [OperatorQueueController::class, 'skip']);
        Route::post('/operator/queue/{ticket}/done', [OperatorQueueController::class, 'done']);
    });
});
