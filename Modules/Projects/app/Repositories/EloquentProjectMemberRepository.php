<?php

namespace Modules\Projects\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;

class EloquentProjectMemberRepository implements ProjectMemberRepository
{
    public function create(Project $project, User $user, ProjectMemberRole $role, \DateTimeInterface $joinedAt): ProjectMember
    {
        return ProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_role' => $role,
            'joined_at' => $joinedAt,
        ]);
    }

    public function delete(Project $project, User $user): void
    {
        ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function exists(Project $project, User $user): bool
    {
        return ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function isManager(Project $project, User $user): bool
    {
        return ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('member_role', ProjectMemberRole::Manager->value)
            ->exists();
    }

    public function allForProject(Project $project): Collection
    {
        return ProjectMember::query()
            ->with('user')
            ->where('project_id', $project->id)
            ->orderBy('member_role')
            ->get();
    }

    public function paginateForProject(Project $project, int $perPage): LengthAwarePaginator
    {
        return ProjectMember::query()
            ->with('user')
            ->where('project_id', $project->id)
            ->orderBy('member_role')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function availableUsers(Project $project): Collection
    {
        return User::query()
            ->whereNotIn('id', ProjectMember::query()->where('project_id', $project->id)->select('user_id'))
            ->orderBy('name')
            ->get();
    }
}
