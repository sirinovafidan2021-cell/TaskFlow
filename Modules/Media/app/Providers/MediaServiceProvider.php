<?php

namespace Modules\Media\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Media\Repositories\EloquentMediaRepository;
use Modules\Media\Repositories\MediaRepository;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('Media', 'config/config.php'), 'media');

        $this->app->bind(MediaRepository::class, EloquentMediaRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(module_path('Media', 'routes/web.php'));
        \Illuminate\Support\Facades\Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', 'active-user', 'throttle:taskflow-api'])->as('api.v1.')->group(module_path('Media', 'routes/api.php'));
        $this->loadMigrationsFrom(module_path('Media', 'database/migrations'));
    }
}
