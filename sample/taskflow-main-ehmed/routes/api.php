<?php

use App\Http\Controllers\Api\V1\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/token', [AuthenticationController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthenticationController::class, 'show']);
        Route::delete('auth/token', [AuthenticationController::class, 'destroy']);
    });
});
