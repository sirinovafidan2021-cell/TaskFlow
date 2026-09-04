<?php

namespace Modules\Activity\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Spatie\Activitylog\Models\Activity;

class ActivityQueryService
{
    /** @return Collection<int, Activity> */
    public function recentForProject(Project $project, int $limit = 5): Collection
    {
        return Activity::query()
            ->with('causer')
            ->where('properties->project_id', $project->id)
            ->latest()
            ->take($limit)
            ->get();
    }

    /** @return Collection<int, Activity> */
    public function recentForTask(Task $task, int $limit = 5): Collection
    {
        return Activity::query()
            ->with('causer')
            ->where('properties->task_id', $task->id)
            ->latest()
            ->take($limit)
            ->get();
    }

    /** @return Collection<int, Activity> */
    public function recentForUser(User $user, int $limit = 8): Collection
    {
        return $this->scopedQuery($user)->latest()->take($limit)->get();
    }

    public function paginate(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $filters = $this->normaliseFilters($filters);

        return $this->scopedQuery($user)
            ->when($filters['event'] ?? null, fn (Builder $query, string $event) => $query->where('event', $event))
            ->when($filters['project_id'] ?? null, fn (Builder $query, int $id) => $query->where('properties->project_id', $id))
            ->when($filters['task_id'] ?? null, fn (Builder $query, int $id) => $query->where('properties->task_id', $id))
            ->when($filters['actor_id'] ?? null, fn (Builder $query, int $id) => $query->where('causer_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return array{events: Collection<int, string>, projects: Collection<int, Project>, tasks: Collection<int, Task>, actors: Collection<int, User>} */
    public function filterOptions(User $user): array
    {
        $activities = $this->scopedQuery($user);
        $properties = (clone $activities)->get(['properties']);
        $projectIds = $properties->map(fn (Activity $activity) => $activity->properties['project_id'] ?? null)->filter()->unique()->values();
        $taskIds = $properties->map(fn (Activity $activity) => $activity->properties['task_id'] ?? null)->filter()->unique()->values();
        $actorIds = (clone $activities)->whereNotNull('causer_id')->pluck('causer_id')->unique()->filter();

        return [
            'events' => (clone $activities)->pluck('event')->unique()->sort()->values(),
            'projects' => Project::query()->whereIn('id', $projectIds)->orderBy('name')->get(),
            'tasks' => Task::query()->withTrashed()->whereIn('id', $taskIds)->orderBy('number')->get(),
            'actors' => User::query()->whereIn('id', $actorIds)->orderBy('name')->get(),
        ];
    }

    private function scopedQuery(User $user): Builder
    {
        $query = Activity::query()->with(['causer', 'subject']);

        if ($user->hasRole(UserRole::Admin->value)) {
            return $query;
        }

        $projectIds = Project::query()
            ->where('owner_id', $user->id)
            ->pluck('id')
            ->merge(Project::query()
                ->whereHas('memberships', fn (Builder $memberships) => $memberships->where('user_id', $user->id))
                ->pluck('id'))
            ->unique()
            ->values();

        return $query->where(function (Builder $scope) use ($projectIds, $user): void {
            $scope->whereIn('properties->project_id', $projectIds->all())
                ->orWhere(function (Builder $userActivity) use ($user): void {
                    $userActivity->where('causer_id', $user->id)->where('subject_type', User::class);
                });
        });
    }

    private function normaliseFilters(array $filters): array
    {
        return [
            'event' => $filters['event'] ?? null,
            'project_id' => $filters['project_id'] ?? $filters['project'] ?? null,
            'task_id' => $filters['task_id'] ?? $filters['task'] ?? null,
            'actor_id' => $filters['actor_id'] ?? $filters['actor'] ?? null,
            'date_from' => $filters['date_from'] ?? $filters['from'] ?? null,
            'date_to' => $filters['date_to'] ?? $filters['to'] ?? null,
        ];
    }
}
