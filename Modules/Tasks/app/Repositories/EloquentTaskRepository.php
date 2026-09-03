<?php

namespace Modules\Tasks\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskLabel;

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
        return Task::query()->with(['project', 'creator', 'assignee', 'labels', 'parent:id,project_id,number,title,type', 'subtasks:id,parent_id,project_id,number,title,type'])
            ->when(filled($filters->q), fn ($query) => $query->where(fn ($query) => $query->where('number', 'like', "%{$filters->q}%")->orWhere('title', 'like', "%{$filters->q}%")->orWhere('description', 'like', "%{$filters->q}%")))
            ->when($filters->statuses !== [], fn ($query) => $query->whereIn('status', $filters->statuses))
            ->when($filters->types !== [], fn ($query) => $query->whereIn('type', $filters->types))
            ->when($filters->priorities !== [], fn ($query) => $query->whereIn('priority', $filters->priorities))
            ->when($filters->projectId, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters->assigneeId, fn ($query, $id) => $query->where('assignee_id', $id))
            ->when($filters->reporterId, fn ($query, $id) => $query->where('creator_id', $id))
            ->when($filters->parentId, fn ($query, $id) => $query->where('parent_id', $id))
            ->when($filters->dueBefore, fn ($query, $date) => $query->whereDate('due_at', '<=', $date))
            ->when($filters->dueAfter, fn ($query, $date) => $query->whereDate('due_at', '>=', $date))
            ->when($filters->overdue, fn ($query) => $query->whereDate('due_at','<',today())->whereNotIn('status',[TaskStatus::Done->value,TaskStatus::Cancelled->value]))
            ->when($filters->labelIds !== [], fn ($query) => $query->whereHas('labels', fn ($labels) => $labels->whereIn('task_labels.id',$filters->labelIds)));
    }

    private function visibleTo($query, User $user)
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            return $query;
        }

        return $query->where(function ($query) use ($user): void {
            $query->whereHas('project', function ($projects) use ($user): void {
                    $projects->where('owner_id', $user->id)
                        ->orWhereHas('memberships', function ($memberships) use ($user): void {
                            $memberships->where('user_id', $user->id);
                        });
                });
        });
    }

    private function sortColumn(TaskFiltersData $filters): string
    {
        return in_array(ltrim($filters->sort,'-'), ['created_at','updated_at','due_at','priority','status','number','rank'], true) ? ltrim($filters->sort,'-') : 'created_at';
    }

    private function sortDirection(TaskFiltersData $filters): string
    {
        return str_starts_with($filters->sort,'-') ? 'desc' : 'asc';
    }

    public function save(Task $task): Task
    {
        $task->save();

        return $task;
    }

    public function findOrFail(int $id): Task
    {
        return Task::query()->with(['project', 'parent', 'subtasks'])->findOrFail($id);
    }

    public function lockForUpdate(Task $task): Task
    {
        return Task::query()->with('project')->whereKey($task->id)->lockForUpdate()->firstOrFail();
    }

    public function hasOpenSubtasks(Task $task): bool
    {
        return $task->subtasks()->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])->exists();
    }

    public function hasSubtasks(Task $task): bool
    {
        return $task->subtasks()->exists();
    }

    public function standardParentsForProject(Project $project): Collection
    {
        return Task::query()->where('project_id', $project->id)->where('type', '!=', TaskType::Subtask->value)->orderBy('number')->get(['id', 'project_id', 'number', 'title', 'type']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function unassignOpenWorkFor(User $user): int
    {
        return Task::query()
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->update(['assignee_id' => null, 'updated_at' => now()]);
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

    public function filterLabelsFor(User $user): Collection
    {
        return TaskLabel::query()->whereIn('project_id', $this->visibleTo(Task::query(), $user)->select('project_id'))->orderBy('name')->get();
    }
}
