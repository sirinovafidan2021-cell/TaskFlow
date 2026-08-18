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
        $this->loadViewsFrom(module_path('Activity', 'resources/views'), 'activity');
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
