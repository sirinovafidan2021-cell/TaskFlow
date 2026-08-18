<?php

namespace Modules\Dashboard\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;

class DashboardService
{
    public function __construct(private readonly ActivityQueryService $activity)
    {
    }

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

        if ($user->hasRole(UserRole::ProjectManager->value)) {
            $memberships->where('member_role', ProjectMemberRole::Manager->value);
        }

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

        if ($user->hasRole(UserRole::ProjectManager->value)) {
            return Task::query()->whereIn('project_id', $projectIds);
        }

        return Task::query()->where('assignee_id', $user->id);
    }

    private function myTasks(User $user)
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
}
