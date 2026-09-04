<?php

namespace Modules\Dashboard\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Repositories\ProjectRepository;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;

class DashboardService
{
    public function __construct(
        private readonly ActivityQueryService $activity,
        private readonly ProjectRepository $projects,
        private readonly TaskRepository $tasks,
    ) {}

    public function summary(User $user): array
    {
        $projects = $this->projects->visibleQueryFor($user);
        $tasks = $this->tasks->visibleQueryFor($user);
        $today = today()->toDateString();
        $closed = [TaskStatus::Done->value, TaskStatus::Cancelled->value];
        $metrics = (clone $tasks)->selectRaw(
            'COUNT(*) AS total_tasks, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS todo, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS in_progress, '
            .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS review, '
            .'SUM(CASE WHEN due_at IS NOT NULL AND due_at < ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) AS overdue, '
            .'SUM(CASE WHEN status = ? AND DATE(completed_at) = ? THEN 1 ELSE 0 END) AS completed_today',
            [TaskStatus::Todo->value, TaskStatus::InProgress->value, TaskStatus::Review->value, $today, ...$closed, TaskStatus::Done->value, $today],
        )->first();

        return [
            'activeProjects' => (clone $projects)->where('status', ProjectStatus::Active->value)->count(),
            'archivedProjects' => (clone $projects)->where('status', ProjectStatus::Archived->value)->count(),
            'totalTasks' => (int) $metrics->total_tasks,
            'todo' => (int) $metrics->todo,
            'inProgress' => (int) $metrics->in_progress,
            'review' => (int) $metrics->review,
            'overdue' => (int) $metrics->overdue,
            'completedToday' => (int) $metrics->completed_today,
            'projectStatusDistribution' => $this->distribution($projects, 'status'),
            'taskTypeDistribution' => $this->distribution($tasks, 'type'),
            'myTasks' => $this->myTasks($user),
            'reportedTasks' => $this->reportedTasks($user),
            'watchedTasks' => $this->watchedTasks($user),
            'overdueTasks' => $this->overdueTasks($user),
            'completedTodayTasks' => $this->completedTodayTasks($user),
            'recentActivity' => $this->activity->recentForUser($user, 8),
        ];
    }

    /** @return Collection<int, Task> */
    public function myTasks(User $user): Collection
    {
        return $this->queue($this->tasks->visibleQueryFor($user)->where('assignee_id', $user->id));
    }

    /** @return Collection<int, Task> */
    public function reportedTasks(User $user): Collection
    {
        return $this->queue($this->tasks->visibleQueryFor($user)->where('creator_id', $user->id));
    }

    /** @return Collection<int, Task> */
    public function watchedTasks(User $user): Collection
    {
        return $this->queue($this->tasks->visibleQueryFor($user)->whereHas('watchers', fn (Builder $watchers) => $watchers->whereKey($user->id)));
    }

    /** @return Collection<int, Task> */
    public function overdueTasks(User $user): Collection
    {
        return $this->queue($this->overdueQuery($user), false);
    }

    /** @return Collection<int, Task> */
    public function completedTodayTasks(User $user): Collection
    {
        return $this->queue($this->tasks->visibleQueryFor($user)
            ->where('status', TaskStatus::Done->value)
            ->whereDate('completed_at', today()->toDateString()), false);
    }

    public function paginateOverdue(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->overdueQuery($user)
            ->with($this->taskRelations())
            ->orderBy('due_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function overdueQuery(User $user): Builder
    {
        return $this->tasks->visibleQueryFor($user)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value]);
    }

    /** @return Collection<int, Task> */
    private function queue(Builder $query, bool $prioritizeDue = true): Collection
    {
        $query->with($this->taskRelations());

        if ($prioritizeDue) {
            $query->orderByRaw(
                'CASE WHEN due_at IS NOT NULL AND due_at < ? AND status NOT IN (?, ?) THEN 0 ELSE 1 END',
                [now(), TaskStatus::Done->value, TaskStatus::Cancelled->value],
            )->orderByRaw('due_at IS NULL')->orderBy('due_at');
        } else {
            $query->latest('updated_at');
        }

        return $query->latest('id')->take(6)->get();
    }

    private function distribution(Builder $query, string $column): array
    {
        return (clone $query)->selectRaw("{$column}, COUNT(*) AS aggregate")
            ->groupBy($column)
            ->orderBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function taskRelations(): array
    {
        return ['project', 'creator', 'assignee', 'labels'];
    }
}
