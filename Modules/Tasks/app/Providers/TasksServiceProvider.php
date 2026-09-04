<?php

namespace Modules\Tasks\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Policies\TaskPolicy;
use Modules\Tasks\Repositories\EloquentTaskAttachmentRepository;
use Modules\Tasks\Repositories\EloquentTaskCommentRepository;
use Modules\Tasks\Repositories\EloquentTaskRepository;
use Modules\Tasks\Repositories\TaskAttachmentRepository;
use Modules\Tasks\Repositories\TaskCommentRepository;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Repositories\TaskLabelRepository;
use Modules\Tasks\Repositories\EloquentTaskLabelRepository;
use Modules\Tasks\Repositories\TaskWatcherRepository;
use Modules\Tasks\Repositories\EloquentTaskWatcherRepository;
use Modules\Tasks\Livewire\TaskFilters;
use Modules\Tasks\Livewire\TaskStatusSelector;
use Modules\Tasks\Livewire\TaskCommentForm;
use Livewire\Livewire;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaskRepository::class, EloquentTaskRepository::class);
        $this->app->bind(TaskCommentRepository::class, EloquentTaskCommentRepository::class);
        $this->app->bind(TaskAttachmentRepository::class, EloquentTaskAttachmentRepository::class);
        $this->app->bind(TaskWatcherRepository::class, EloquentTaskWatcherRepository::class);
        $this->app->bind(TaskLabelRepository::class, EloquentTaskLabelRepository::class);
    }

    public function boot(): void
    {
        Livewire::component('tasks.task-filters', TaskFilters::class);
        Livewire::component('tasks.task-status-selector', TaskStatusSelector::class);
        Livewire::component('tasks.task-comment-form', TaskCommentForm::class);
        $this->loadRoutesFrom(module_path('Tasks', 'routes/web.php'));
        \Illuminate\Support\Facades\Route::prefix('api/v1')->middleware(['api', 'auth:sanctum', 'active-user', 'throttle:taskflow-api'])->as('api.v1.')->group(module_path('Tasks', 'routes/api.php'));
        $this->loadViewsFrom(module_path('Tasks', 'resources/views'), 'tasks');
        $this->loadMigrationsFrom(module_path('Tasks', 'database/migrations'));
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
