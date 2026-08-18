<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Http\Controllers\Api\ProjectController;
use Modules\Projects\Http\Controllers\Api\ProjectMemberController;

Route::middleware(['api', 'auth:sanctum', 'api.verified'])
    ->prefix('api/v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::apiResource('projects', ProjectController::class);
        Route::get('projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members.index');
        Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
        Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    });
