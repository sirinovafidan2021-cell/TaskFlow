<?php

namespace Modules\Projects\Services;

use App\Enums\UserRole;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;

class ProjectMemberService
{
    public function __construct(private readonly ActivityRecorder $activity) {}

    public function addMember(Project $project, User $user, ProjectMemberRole $role, ?DateTimeInterface $joinedAt = null, ?User $actor = null): ProjectMember
    {
        if (! $project->exists || ! $user->exists) {
            throw new LogicException('Projects and users must be persisted before membership is created.');
        }

        if ($this->isMember($project, $user)) {
            throw new LogicException('This user is already a project member.');
        }

        $membership = ProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_role' => $role,
            'joined_at' => $joinedAt ?? now(),
        ]);
        $this->activity->record('project.member_added', $actor ?? $user, $project, [
            'project_id' => $project->id,
            'member_id' => $user->id,
            'member_name' => $user->name ?: $user->email,
            'member_role' => $role->value,
        ]);

        return $membership;
    }

    public function removeMember(Project $project, User $user, ?User $actor = null): void
    {
        if ($project->owner_id === $user->id) {
            throw new LogicException('The project owner cannot be removed from project membership.');
        }

        ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();
        $this->activity->record('project.member_removed', $actor ?? $user, $project, [
            'project_id' => $project->id,
            'member_id' => $user->id,
            'member_name' => $user->name ?: $user->email,
        ]);
    }

    public function isMember(Project $project, User $user): bool
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

    public function canManage(Project $project, User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value)
            || $project->owner_id === $user->id
            || $this->isManager($project, $user);
    }

    /** @return Collection<int, ProjectMember> */
    public function memberships(Project $project): Collection
    {
        return ProjectMember::query()->with('user')->where('project_id', $project->id)->orderBy('member_role')->get();
    }

    /** @return Collection<int, User> */
    public function availableUsers(Project $project): Collection
    {
        return User::query()
            ->whereNotIn('id', ProjectMember::query()->where('project_id', $project->id)->select('user_id'))
            ->orderBy('name')
            ->get();
    }
}
