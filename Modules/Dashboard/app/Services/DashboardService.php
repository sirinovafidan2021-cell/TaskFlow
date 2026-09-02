<?php

namespace Modules\Dashboard\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

class DashboardService
{
    public function __construct(private readonly ActivityQueryService $activity) {}

    public function summary(User $user): array
    {
        $projects = $this->projectsFor($user);
        $tasks = $this->tasksFor($user, (clone $projects)->pluck('id')->all());

        return [
            'activeProjects' => (clone $projects)->where('status', ProjectStatus::Active->value)->count(),
            'archivedProjects' => (clone $projects)->where('status', ProjectStatus::Archived->value)->count(),
            'totalTasks' => (clone $tasks)->count(),
            'todo' => (clone $tasks)->where('status', TaskStatus::Todo->value)->count(),
            'inProgress' => (clone $tasks)->where('status', TaskStatus::InProgress->value)->count(),
            'review' => (clone $tasks)->where('status', TaskStatus::Review->value)->count(),
            'overdue' => (clone $tasks)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
                ->count(),
            'completedToday' => (clone $tasks)
                ->where('status', TaskStatus::Done->value)
                ->whereDate('completed_at', today())
                ->count(),
            'myTasks' => $this->myTasks($user),
            'recentActivity' => $this->activity->recentForUser($user, 8),
        ];
    }

    private function projectsFor(User $user): Builder
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            return Project::query();
        }

        $memberships = ProjectMember::query()->where('user_id', $user->id);

        $projectIds = Project::query()->where('owner_id', $user->id)->pluck('id')
            ->merge($memberships->pluck('project_id'))
            ->unique()
            ->values();

        return Project::query()->whereIn('id', $projectIds->all());
    }

    private function tasksFor(User $user, array $projectIds): Builder
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            return Task::query();
        }

        return Task::query()->whereIn('project_id', $projectIds);
    }

    /** @return Collection<int, Task> */
    public function myTasks(User $user): Collection
    {
        return Task::query()
            ->with(['project', 'assignee'])
            ->where('assignee_id', $user->id)
            ->orderByRaw(
                'CASE WHEN due_at IS NOT NULL AND due_at < ? AND status NOT IN (?, ?) THEN 0 ELSE 1 END',
                [now(), TaskStatus::Done->value, TaskStatus::Cancelled->value],
            )
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->latest()
            ->take(6)
            ->get();
    }

    public function paginateOverdue(User $user, int $perPage): LengthAwarePaginator
    {
        $projects = $this->projectsFor($user);
        $projectIds = (clone $projects)->pluck('id')->all();

        return $this->tasksFor($user, $projectIds)
            ->with(['project', 'creator', 'assignee'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->orderBy('due_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
