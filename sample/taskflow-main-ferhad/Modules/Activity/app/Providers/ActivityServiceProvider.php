<?php
namespace Modules\Activity\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Activity\Policies\ActivityPolicy;
class ActivityServiceProvider extends ServiceProvider { public function boot(): void { $this->loadRoutesFrom(module_path('Activity','routes/web.php')); $this->loadViewsFrom(module_path('Activity','resources/views'),'activity'); Gate::policy(\Spatie\Activitylog\Models\Activity::class, ActivityPolicy::class); } }
