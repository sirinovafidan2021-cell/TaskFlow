<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\Api\TaskController;

Route::middleware(['api', 'auth:sanctum', 'api.verified'])
    ->prefix('api/v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::apiResource('tasks', TaskController::class);
    });
