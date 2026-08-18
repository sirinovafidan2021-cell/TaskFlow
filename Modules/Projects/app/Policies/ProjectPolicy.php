<?php

namespace Modules\Projects\Policies;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Modules\Projects\Enums\ProjectMemberRole;
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
        return $user->hasPermissionTo(PermissionName::ProjectsUpdate->value)
            && ($user->hasRole(UserRole::Admin->value) || $project->owner_id === $user->id);
    }

    public function archive(User $user, Project $project): bool
    {
        return $user->hasPermissionTo(PermissionName::ProjectsArchive->value)
            && ($user->hasRole(UserRole::Admin->value) || $project->owner_id === $user->id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->archive($user, $project);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->hasPermissionTo(PermissionName::ProjectsMembersManage->value)
            && ($user->hasRole(UserRole::Admin->value)
                || $project->owner_id === $user->id
                || $project->memberships()
                    ->where('user_id', $user->id)
                    ->where('member_role', ProjectMemberRole::Manager->value)
                    ->exists());
    }
}
