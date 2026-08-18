<?php

namespace Modules\Projects\Repositories\Eloquent;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Repositories\Contracts\ProjectMemberRepositoryInterface;

class EloquentProjectMemberRepository implements ProjectMemberRepositoryInterface
{
    public function create(Project $project, User $user, ProjectMemberRole $role, DateTimeInterface $joinedAt): ProjectMember
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

    public function availableUsersForProject(Project $project): Collection
    {
        return User::query()
            ->whereNotIn('id', ProjectMember::query()
                ->where('project_id', $project->id)
                ->select('user_id'))
            ->orderBy('name')
            ->get();
    }
}
