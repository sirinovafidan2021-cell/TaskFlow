<?php

namespace Modules\Tasks\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Policies\TaskPolicy;
use Modules\Tasks\Repositories\Contracts\TaskAttachmentRepositoryInterface;
use Modules\Tasks\Repositories\Contracts\TaskCommentRepositoryInterface;
use Modules\Tasks\Repositories\Contracts\TaskRepositoryInterface;
use Modules\Tasks\Repositories\Eloquent\EloquentTaskAttachmentRepository;
use Modules\Tasks\Repositories\Eloquent\EloquentTaskCommentRepository;
use Modules\Tasks\Repositories\Eloquent\EloquentTaskRepository;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);
        $this->app->bind(TaskCommentRepositoryInterface::class, EloquentTaskCommentRepository::class);
        $this->app->bind(TaskAttachmentRepositoryInterface::class, EloquentTaskAttachmentRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(module_path('Tasks', 'routes/web.php'));
        $this->loadRoutesFrom(module_path('Tasks', 'routes/api.php'));
        $this->loadViewsFrom(module_path('Tasks', 'resources/views'), 'tasks');
        $this->loadMigrationsFrom(module_path('Tasks', 'database/migrations'));
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
