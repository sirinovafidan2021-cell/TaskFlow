<?php

namespace Modules\Projects\Policies;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ProjectsView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ProjectsCreate->value);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo(PermissionName::ProjectsView->value)
            && ($user->hasRole(UserRole::Admin->value)
                || $project->owner_id === $user->id
                || $project->members()->whereKey($user->id)->exists());
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasRole(UserRole::Admin->value) || $project->owner_id === $user->id || $this->isManager($project, $user);
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    private function isManager(Project $project, User $user): bool { return $project->memberships()->where('user_id',$user->id)->where('member_role',ProjectMemberRole::Manager->value)->exists(); }
}
