<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::prefix('/verify')->group(function (): void {
        Route::post('/resend-code', [AuthController::class, 'resendValidationCode']);
        Route::post('/validate-code', [AuthController::class, 'validateCode']);
        Route::post('/send-validation-code', [AuthController::class, 'sendValidationCode']);
    });
});
