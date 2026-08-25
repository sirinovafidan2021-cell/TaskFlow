<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\Api\V1\ActivityController as ApiActivityController;
use Modules\Dashboard\Http\Controllers\Api\V1\DashboardController as ApiDashboardController;
use Modules\Projects\Http\Controllers\Api\V1\ProjectController as ApiProjectController;
use Modules\Projects\Http\Controllers\Api\V1\ProjectMemberController as ApiProjectMemberController;
use Modules\Tasks\Http\Controllers\Api\V1\TaskAttachmentController as ApiTaskAttachmentController;
use Modules\Tasks\Http\Controllers\Api\V1\TaskCommentController as ApiTaskCommentController;
use Modules\Tasks\Http\Controllers\Api\V1\TaskController as ApiTaskController;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:taskflow-api'])->group(function (): void {
    Route::get('/tokens', [ApiTokenController::class, 'index']);
    Route::post('/tokens', [ApiTokenController::class, 'store']);
    Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy']);

    Route::middleware('abilities:projects:read')->group(function (): void {
        Route::get('/projects', [ApiProjectController::class, 'index']);
        Route::get('/projects/{project}', [ApiProjectController::class, 'show']);
        Route::get('/projects/{project}/members', [ApiProjectMemberController::class, 'index']);
    });
    Route::middleware('abilities:projects:write')->group(function (): void {
        Route::post('/projects', [ApiProjectController::class, 'store']);
        Route::match(['put', 'patch'], '/projects/{project}', [ApiProjectController::class, 'update']);
        Route::post('/projects/{project}/activate', [ApiProjectController::class, 'activate']);
        Route::post('/projects/{project}/archive', [ApiProjectController::class, 'archive']);
        Route::post('/projects/{project}/members', [ApiProjectMemberController::class, 'store']);
        Route::delete('/projects/{project}/members/{user}', [ApiProjectMemberController::class, 'destroy']);
    });

    Route::middleware('abilities:tasks:read')->group(function (): void {
        Route::get('/tasks', [ApiTaskController::class, 'index']);
        Route::get('/tasks/{task}', [ApiTaskController::class, 'show']);
        Route::get('/tasks/{task}/comments', [ApiTaskCommentController::class, 'index']);
        Route::get('/tasks/{task}/attachments', [ApiTaskAttachmentController::class, 'index']);
        Route::get('/tasks/{task}/attachments/{attachment}/download', [ApiTaskAttachmentController::class, 'download'])->name('api.v1.tasks.attachments.download');
    });
    Route::middleware('abilities:tasks:write')->group(function (): void {
        Route::post('/tasks', [ApiTaskController::class, 'store']);
        Route::match(['put', 'patch'], '/tasks/{task}', [ApiTaskController::class, 'update']);
        Route::delete('/tasks/{task}', [ApiTaskController::class, 'destroy']);
        Route::patch('/tasks/{task}/assignee', [ApiTaskController::class, 'assign']);
        Route::patch('/tasks/{task}/status', [ApiTaskController::class, 'changeStatus']);
        Route::post('/tasks/{task}/attachments', [ApiTaskAttachmentController::class, 'store']);
        Route::delete('/tasks/{task}/attachments/{attachment}', [ApiTaskAttachmentController::class, 'destroy']);
    });
    Route::middleware('abilities:comments:write')->group(function (): void {
        Route::post('/tasks/{task}/comments', [ApiTaskCommentController::class, 'store']);
        Route::delete('/tasks/{task}/comments/{comment}', [ApiTaskCommentController::class, 'destroy']);
    });

    Route::middleware('abilities:activity:read')->group(function (): void {
        Route::get('/activity', [ApiActivityController::class, 'index']);
        Route::get('/projects/{project}/activity', [ApiActivityController::class, 'forProject']);
        Route::get('/tasks/{task}/activity', [ApiActivityController::class, 'forTask']);
    });

    Route::middleware('abilities:dashboard:read')->prefix('dashboard')->group(function (): void {
        Route::get('/summary', [ApiDashboardController::class, 'summary']);
        Route::get('/my-tasks', [ApiDashboardController::class, 'myTasks']);
        Route::get('/overdue', [ApiDashboardController::class, 'overdue']);
    });
});
