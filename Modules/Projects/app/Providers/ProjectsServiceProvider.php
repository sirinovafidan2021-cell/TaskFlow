<?php

namespace Modules\Projects\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Projects\Models\Project;
use Modules\Projects\Policies\ProjectPolicy;
use Modules\Projects\Repositories\EloquentProjectMemberRepository;
use Modules\Projects\Repositories\EloquentProjectRepository;
use Modules\Projects\Repositories\ProjectMemberRepository;
use Modules\Projects\Repositories\ProjectRepository;

class ProjectsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectRepository::class, EloquentProjectRepository::class);
        $this->app->bind(ProjectMemberRepository::class, EloquentProjectMemberRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(module_path('Projects', 'routes/web.php'));
        \Illuminate\Support\Facades\Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', 'active-user', 'throttle:taskflow-api'])->as('api.v1.')->group(module_path('Projects', 'routes/api.php'));
        $this->loadViewsFrom(module_path('Projects', 'resources/views'), 'projects');
        $this->loadMigrationsFrom(module_path('Projects', 'database/migrations'));

        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
