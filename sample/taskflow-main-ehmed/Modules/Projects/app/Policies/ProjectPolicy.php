<?php

namespace Modules\Projects\Policies;

use App\Models\User;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;

final class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($this->isProjectManager($user) && $project->owner_id === $user->id) {
            return true;
        }

        return ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->isProjectManager($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isProjectManager($user)
            && $project->owner_id === $user->id
            && $project->status !== 'archived';
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->isProjectManager($user)
            && $project->owner_id === $user->id;
    }

    private function isProjectManager(User $user): bool
    {
        return $user->hasRole('project_manager');
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->isProjectManager($user)
            && $project->owner_id === $user->id
            && $project->status !== 'archived';
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isProjectManager($user)
            && $project->owner_id === $user->id;
    }
}
