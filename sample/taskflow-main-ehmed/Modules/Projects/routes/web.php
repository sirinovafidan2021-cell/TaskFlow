<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Http\Controllers\Web\ProjectController;

Route::middleware('auth')->group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index');

    Route::get('/projects/create', [ProjectController::class, 'create'])
        ->name('projects.create');

    Route::post('/projects', [ProjectController::class, 'store'])
        ->name('projects.store');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->name('projects.show');

    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
        ->name('projects.edit');

    Route::put('/projects/{project}', [ProjectController::class, 'update'])
        ->name('projects.update');

    Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])
        ->name('projects.archive');

    Route::get('/projects/{project}/members', [ProjectController::class, 'members'])
        ->name('projects.members');

    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])
        ->name('projects.members.store');

    Route::delete('/projects/{project}/members/{projectMember}', [ProjectController::class, 'removeMember'])
        ->name('projects.members.destroy');
});
