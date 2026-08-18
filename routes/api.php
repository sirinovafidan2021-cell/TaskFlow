<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('api.v1.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');
        Route::get('/verified-user', [AuthController::class, 'verifiedUser'])->middleware('api.verified')->name('verified-user');
    });
});
