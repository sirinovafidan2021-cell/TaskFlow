<?php
use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\Api\V1\TaskController;
use Modules\Tasks\Http\Controllers\Api\V1\TaskCommentController;
use Modules\Tasks\Http\Controllers\Api\V1\TaskAttachmentController;
Route::middleware('abilities:tasks:read')->group(function (): void {
    Route::get('/tasks',[TaskController::class,'index'])->name('tasks.index'); Route::get('/tasks/{task}',[TaskController::class,'show'])->name('tasks.show');
    Route::get('/tasks/{task}/comments',[TaskCommentController::class,'index'])->name('tasks.comments.index'); Route::get('/tasks/{task}/media',[TaskAttachmentController::class,'index'])->name('tasks.media.index'); Route::get('/tasks/{task}/media/{attachment}/preview',[TaskAttachmentController::class,'preview'])->name('tasks.media.preview'); Route::get('/tasks/{task}/media/{attachment}/download',[TaskAttachmentController::class,'download'])->name('tasks.media.download');
});
Route::middleware('abilities:tasks:write')->group(function (): void {
    Route::post('/tasks',[TaskController::class,'store'])->name('tasks.store'); Route::match(['put','patch'],'/tasks/{task}',[TaskController::class,'update'])->name('tasks.update'); Route::delete('/tasks/{task}',[TaskController::class,'destroy'])->name('tasks.destroy'); Route::patch('/tasks/{task}/assignee',[TaskController::class,'assign'])->name('tasks.assign'); Route::patch('/tasks/{task}/status',[TaskController::class,'changeStatus'])->name('tasks.status'); Route::post('/tasks/{task}/media',[TaskAttachmentController::class,'store'])->name('tasks.media.store'); Route::delete('/tasks/{task}/media/{attachment}',[TaskAttachmentController::class,'destroy'])->name('tasks.media.destroy');
});
Route::middleware('abilities:comments:write')->group(function (): void { Route::post('/tasks/{task}/comments',[TaskCommentController::class,'store'])->name('tasks.comments.store'); Route::delete('/tasks/{task}/comments/{comment}',[TaskCommentController::class,'destroy'])->name('tasks.comments.destroy'); });
