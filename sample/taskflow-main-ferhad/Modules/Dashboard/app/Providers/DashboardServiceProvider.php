<?php
namespace Modules\Dashboard\Providers;
use Illuminate\Support\ServiceProvider;
class DashboardServiceProvider extends ServiceProvider { public function boot(): void { $this->loadRoutesFrom(module_path('Dashboard','routes/web.php')); $this->loadViewsFrom(module_path('Dashboard','resources/views'),'dashboard'); } }
