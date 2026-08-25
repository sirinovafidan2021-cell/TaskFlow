<?php

namespace Modules\Projects\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;

class EloquentProjectRepository implements ProjectRepository
{
    public function paginateFor(User $user, ?string $search, ?string $status, int $perPage = 12): LengthAwarePaginator
    {
        return $this->baseQuery($search, $status)
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

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool
    {
        return Project::query()
            ->where('slug', $slug)
            ->when($excludingProjectId, fn ($query) => $query->where('id', '!=', $excludingProjectId))
            ->exists();
    }
}
