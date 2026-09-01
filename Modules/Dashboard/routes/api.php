<?php
use Illuminate\Support\Facades\Route; use Modules\Dashboard\Http\Controllers\Api\V1\DashboardController;
Route::middleware('abilities:dashboard:read')->prefix('dashboard')->group(function (): void { Route::get('/summary',[DashboardController::class,'summary'])->name('dashboard.summary'); Route::get('/my-tasks',[DashboardController::class,'myTasks'])->name('dashboard.my-tasks'); Route::get('/overdue',[DashboardController::class,'overdue'])->name('dashboard.overdue'); });
