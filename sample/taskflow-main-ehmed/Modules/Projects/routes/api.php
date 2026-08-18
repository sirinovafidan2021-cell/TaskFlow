<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Http\Controllers\Api\V1\ProjectController;

Route::middleware(['auth:sanctum', 'abilities:projects:read'])->prefix('v1')->group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('v1.projects.index');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->name('v1.projects.show');

    Route::get('/projects/{project}/members', [ProjectController::class, 'members'])
        ->name('v1.projects.members.index');
});

Route::middleware(['auth:sanctum', 'abilities:projects:write'])->prefix('v1')->group(function (): void {
    Route::post('/projects', [ProjectController::class, 'store'])
        ->name('v1.projects.store');

    Route::put('/projects/{project}', [ProjectController::class, 'update'])
        ->name('v1.projects.update');

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->name('v1.projects.destroy');

    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])
        ->name('v1.projects.members.store');

    Route::delete('/projects/{project}/members/{projectMember}', [ProjectController::class, 'removeMember'])
        ->name('v1.projects.members.destroy');
});
