<?php

namespace Modules\Projects\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Data\ProjectFiltersData;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function paginateVisibleTo(User $actor, ProjectFiltersData $filters): LengthAwarePaginator
    {
        return Project::query()
            ->with('owner')
            ->withCount('memberships')
            ->when(! $actor->hasRole(UserRole::Admin->value), function ($query) use ($actor): void {
                $query->where(function ($query) use ($actor): void {
                    if ($actor->hasRole(UserRole::ProjectManager->value)) {
                        $query->where('owner_id', $actor->id)
                            ->orWhereHas('memberships', function ($memberships) use ($actor): void {
                                $memberships
                                    ->where('user_id', $actor->id)
                                    ->where('member_role', ProjectMemberRole::Manager->value);
                            });

                        return;
                    }

                    $query->whereHas('memberships', function ($memberships) use ($actor): void {
                        $memberships->where('user_id', $actor->id);
                    });
                });
            })
            ->when($filters->search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status->value))
            ->latest()
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function save(Project $project): Project
    {
        $project->save();

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool
    {
        return Project::query()
            ->where('slug', $slug)
            ->when($excludingProjectId, fn ($query) => $query->where('id', '!=', $excludingProjectId))
            ->exists();
    }
}
