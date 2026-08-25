<?php

namespace Modules\Tasks\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

class EloquentTaskRepository implements TaskRepository
{
    public function paginateFor(User $user, TaskFiltersData $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->visibleTo($this->baseQuery($filters), $user)
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->paginate($perPage)
            ->withQueryString();
    }

    private function baseQuery(TaskFiltersData $filters)
    {
        return Task::query()->with(['project', 'creator', 'assignee'])
            ->when(filled($filters->q), fn ($query) => $query->where(fn ($query) => $query->where('number', 'like', "%{$filters->q}%")->orWhere('title', 'like', "%{$filters->q}%")->orWhere('description', 'like', "%{$filters->q}%")))
            ->when(TaskStatus::tryFrom((string) $filters->status), fn ($query, $status) => $query->where('status', $status->value))
            ->when(TaskPriority::tryFrom((string) $filters->priority), fn ($query, $priority) => $query->where('priority', $priority->value))
            ->when($filters->projectId, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters->assigneeId, fn ($query, $id) => $query->where('assignee_id', $id))
            ->when($filters->dueBefore, fn ($query, $date) => $query->whereDate('due_at', '<=', $date));
    }

    private function visibleTo($query, User $user)
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            return $query;
        }

        return $query->where(function ($query) use ($user): void {
            $query->where('assignee_id', $user->id)
                ->orWhereHas('project', function ($projects) use ($user): void {
                    $projects->where('owner_id', $user->id)
                        ->orWhereHas('memberships', function ($memberships) use ($user): void {
                            $memberships->where('user_id', $user->id)
                                ->where('member_role', ProjectMemberRole::Manager->value);
                        });
                });
        });
    }

    private function sortColumn(TaskFiltersData $filters): string
    {
        return in_array($filters->sort, ['created_at', 'due_at', 'priority', 'status', 'number'], true)
            ? $filters->sort
            : 'created_at';
    }

    private function sortDirection(TaskFiltersData $filters): string
    {
        return $filters->direction === 'asc' ? 'asc' : 'desc';
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

    public function filterProjectsFor(User $user): Collection
    {
        return Project::query()
            ->whereIn('id', $this->visibleTo(Task::query(), $user)->select('project_id'))
            ->orderBy('name')
            ->get();
    }

    public function filterUsersFor(User $user): Collection
    {
        return User::query()
            ->whereIn('id', $this->visibleTo(Task::query(), $user)->whereNotNull('assignee_id')->select('assignee_id'))
            ->orderBy('name')
            ->get();
    }
}
