<?php

use Illuminate\Support\Facades\Route;
use Modules\Activity\Http\Controllers\ActivityController;

Route::middleware(['web', 'auth', 'active-user'])->get('/activity', [ActivityController::class, 'index'])->name('activity.index');
