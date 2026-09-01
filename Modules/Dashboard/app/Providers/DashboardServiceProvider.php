<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(module_path('Dashboard', 'routes/web.php'));
        \Illuminate\Support\Facades\Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', 'active-user', 'throttle:taskflow-api'])->as('api.v1.')->group(module_path('Dashboard', 'routes/api.php'));
        $this->loadViewsFrom(module_path('Dashboard', 'resources/views'), 'dashboard');
    }
}
