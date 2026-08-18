<?php

namespace Modules\Tasks\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\Contracts\TaskRepositoryInterface;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function paginate(TaskFiltersData $filters, User $actor): LengthAwarePaginator
    {
        $sort = in_array($filters->sort, ['created_at', 'due_at', 'priority', 'status', 'number'], true)
            ? $filters->sort
            : 'created_at';
        $direction = $filters->direction === 'asc' ? 'asc' : 'desc';

        return $this->visibleTo($actor)
            ->with(['project', 'creator', 'assignee'])
            ->when(filled($filters->q), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->where('number', 'like', "%{$filters->q}%")
                        ->orWhere('title', 'like', "%{$filters->q}%")
                        ->orWhere('description', 'like', "%{$filters->q}%");
                });
            })
            ->when(TaskStatus::tryFrom((string) $filters->status), fn (Builder $query, TaskStatus $status) => $query->where('status', $status->value))
            ->when(TaskPriority::tryFrom((string) $filters->priority), fn (Builder $query, TaskPriority $priority) => $query->where('priority', $priority->value))
            ->when($filters->projectId, fn (Builder $query, int $projectId) => $query->where('project_id', $projectId))
            ->when($filters->assigneeId, fn (Builder $query, int $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($filters->dueBefore, fn (Builder $query, string $dueBefore) => $query->whereDate('due_at', '<=', $dueBefore))
            ->orderBy($sort, $direction)
            ->paginate(12)
            ->withQueryString();
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

    public function filterProjects(User $actor): Collection
    {
        return Project::query()
            ->whereIn('id', $this->visibleTo($actor)->select('project_id')->distinct())
            ->orderBy('name')
            ->get();
    }

    public function filterUsers(User $actor): Collection
    {
        return User::query()
            ->whereIn('id', $this->visibleTo($actor)
                ->whereNotNull('assignee_id')
                ->select('assignee_id')
                ->distinct())
            ->orderBy('name')
            ->get();
    }

    /** @return Builder<Task> */
    private function visibleTo(User $actor): Builder
    {
        $query = Task::query();

        if ($actor->hasRole(UserRole::Admin->value)) {
            return $query;
        }

        if ($actor->hasRole(UserRole::ProjectManager->value)) {
            return $query->whereHas('project', function (Builder $project) use ($actor): void {
                $project->where('owner_id', $actor->id)
                    ->orWhereHas('memberships', function (Builder $memberships) use ($actor): void {
                        $memberships
                            ->where('user_id', $actor->id)
                            ->where('member_role', ProjectMemberRole::Manager->value);
                    });
            });
        }

        return $query->where('assignee_id', $actor->id);
    }
}
