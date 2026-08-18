<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\Api\ActivityController;

Route::middleware(['api', 'auth:sanctum', 'api.verified'])
    ->prefix('api/v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    });
