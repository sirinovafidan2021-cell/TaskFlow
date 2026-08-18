<?php

namespace Modules\Projects\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Projects\Models\Project;
use Modules\Projects\Policies\ProjectPolicy;
use Modules\Projects\Repositories\Contracts\ProjectMemberRepositoryInterface;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;
use Modules\Projects\Repositories\Eloquent\EloquentProjectMemberRepository;
use Modules\Projects\Repositories\Eloquent\EloquentProjectRepository;

final class ProjectsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProjectRepositoryInterface::class,
            EloquentProjectRepository::class,
        );

        $this->app->bind(
            ProjectMemberRepositoryInterface::class,
            EloquentProjectMemberRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);

        $this->loadViewsFrom(
            module_path('Projects', 'resources/views'),
            'projects',
        );
    }
}
