<?php

namespace Modules\Projects\Services;

use App\Enums\UserRole;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Repositories\ProjectMemberRepository;

class ProjectMemberService
{
    public function __construct(
        private readonly ProjectMemberRepository $members,
        private readonly ActivityRecorder $activity,
    ) {}

    public function addMember(Project $project, User $user, ProjectMemberRole $role, ?DateTimeInterface $joinedAt = null, ?User $actor = null): ProjectMember
    {
        $this->ensureMutable($project);

        if (! $project->exists || ! $user->exists) {
            throw new LogicException('Projects and users must be persisted before membership is created.');
        }

        if ($this->isMember($project, $user)) {
            throw new LogicException('This user is already a project member.');
        }

        $membership = $this->members->create($project, $user, $role, $joinedAt ?? now());
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
        $this->ensureMutable($project);

        if ($project->owner_id === $user->id) {
            throw new LogicException('The project owner cannot be removed from project membership.');
        }

        if (! $this->isMember($project, $user)) {
            throw new LogicException('The user is not a project member.');
        }

        $this->members->delete($project, $user);
        $this->activity->record('project.member_removed', $actor ?? $user, $project, [
            'project_id' => $project->id,
            'member_id' => $user->id,
            'member_name' => $user->name ?: $user->email,
        ]);
    }

    public function isMember(Project $project, User $user): bool
    {
        return $this->members->exists($project, $user);
    }

    public function isManager(Project $project, User $user): bool
    {
        return $this->members->isManager($project, $user);
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
        return $this->members->allForProject($project);
    }

    public function paginateMemberships(Project $project, int $perPage): LengthAwarePaginator
    {
        return $this->members->paginateForProject($project, $perPage);
    }

    /** @return Collection<int, User> */
    public function availableUsers(Project $project): Collection
    {
        return $this->members->availableUsers($project);
    }

    private function ensureMutable(Project $project): void
    {
        if ($project->status === ProjectStatus::Archived) {
            throw new LogicException('Archived projects are read-only.');
        }
    }
}
