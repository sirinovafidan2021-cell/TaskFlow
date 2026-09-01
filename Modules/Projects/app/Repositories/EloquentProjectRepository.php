<?php

namespace Modules\Projects\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Data\ProjectFiltersData;
use Modules\Tasks\Models\Task;

class EloquentProjectRepository implements ProjectRepository
{
    public function findOrFail(int $id): Project { return Project::query()->findOrFail($id); }
    public function paginateFor(User $user, ProjectFiltersData $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->baseQuery($filters->search, $filters->status)
            ->when(! $user->hasRole(UserRole::Admin->value), function ($query) use ($user): void {
                $query->where(function ($query) use ($user): void {
                    $query->where('owner_id', $user->id)
                        ->orWhereHas('members', fn ($members) => $members->whereKey($user->id));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    private function baseQuery(?string $search, ?string $status)
    {
        return Project::query()
            ->with('owner')
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(ProjectStatus::tryFrom((string) $status), function ($query, ProjectStatus $projectStatus): void {
                $query->where('status', $projectStatus->value);
            });
    }

    public function save(Project $project): Project
    {
        $project->save();

        return $project;
    }

    public function lockForUpdate(Project $project): Project
    {
        return Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
    }

    public function hasAllocatedIssues(Project $project): bool
    {
        return Task::query()->where('project_id', $project->id)->exists();
    }

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool
    {
        return Project::query()
            ->where('slug', $slug)
            ->when($excludingProjectId, fn ($query) => $query->where('id', '!=', $excludingProjectId))
            ->exists();
    }
}
