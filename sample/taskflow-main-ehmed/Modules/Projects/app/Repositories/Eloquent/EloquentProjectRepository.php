<?php

namespace Modules\Projects\Repositories\Eloquent;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;

final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        $query = Project::query();

        if (! $user->hasRole('Admin')) {
            $query->where(function ($query) use ($user): void {
                $query
                    ->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($query) use ($user): void {
                        $query->where('user_id', $user->id);
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(int $id): Project
    {
        return Project::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Project
    {
        return Project::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        $project->update($attributes);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
