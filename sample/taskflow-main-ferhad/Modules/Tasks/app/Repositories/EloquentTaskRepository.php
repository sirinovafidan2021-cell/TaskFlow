<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

class EloquentTaskRepository implements TaskRepository
{
    public function paginate(TaskFiltersData $filters): LengthAwarePaginator
    {
        $columns = ['created_at', 'due_at', 'priority', 'status', 'number'];
        $sort = in_array($filters->sort, $columns, true) ? $filters->sort : 'created_at';
        $direction = $filters->direction === 'asc' ? 'asc' : 'desc';

        return Task::query()->with(['project', 'creator', 'assignee'])
            ->when(filled($filters->q), fn ($query) => $query->where(fn ($query) => $query->where('number', 'like', "%{$filters->q}%")->orWhere('title', 'like', "%{$filters->q}%")->orWhere('description', 'like', "%{$filters->q}%")))
            ->when(TaskStatus::tryFrom((string) $filters->status), fn ($query, $status) => $query->where('status', $status->value))
            ->when(TaskPriority::tryFrom((string) $filters->priority), fn ($query, $priority) => $query->where('priority', $priority->value))
            ->when($filters->projectId, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters->assigneeId, fn ($query, $id) => $query->where('assignee_id', $id))
            ->when($filters->dueBefore, fn ($query, $date) => $query->whereDate('due_at', '<=', $date))
            ->orderBy($sort, $direction)->paginate(12)->withQueryString();
    }

    public function save(Task $task): Task
    {
        $task->save();

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function filterProjects(): Collection
    {
        return Project::query()->orderBy('name')->get();
    }

    public function filterUsers(): Collection
    {
        return User::query()->orderBy('name')->get();
    }
}
