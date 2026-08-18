<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\Api\DashboardController;

Route::middleware(['api', 'auth:sanctum', 'api.verified'])
    ->prefix('api/v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    });
