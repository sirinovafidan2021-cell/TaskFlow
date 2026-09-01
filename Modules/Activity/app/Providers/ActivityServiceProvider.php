<?php

namespace Modules\Activity\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Activity\Policies\ActivityPolicy;
use Spatie\Activitylog\Models\Activity;

class ActivityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(module_path('Activity', 'routes/web.php'));
        \Illuminate\Support\Facades\Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', 'active-user', 'throttle:taskflow-api'])->as('api.v1.')->group(module_path('Activity', 'routes/api.php'));
        $this->loadViewsFrom(module_path('Activity', 'resources/views'), 'activity');
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
