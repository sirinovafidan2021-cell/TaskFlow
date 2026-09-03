<?php

namespace Modules\Projects\Services;

use App\Enums\UserRole;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Data\UpdateProjectMemberData;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Exceptions\MemberHasOpenAssignments;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Repositories\ProjectMemberRepository;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class ProjectMemberService
{
    public function __construct(
        private readonly ProjectMemberRepository $members,
        private readonly ActivityRecorder $activity,
        private readonly TaskWatcherRepository $watchers,
    ) {}

    public function addMember(Project $project, User $user, ProjectMemberRole $role, ?DateTimeInterface $joinedAt = null, ?User $actor = null): ProjectMember
    {
        return DB::transaction(function () use ($project, $user, $role, $joinedAt, $actor): ProjectMember {
            $this->ensureMutable($project);

            if (! $project->exists || ! $user->exists) {
                throw new LogicException('Projects and users must be persisted before membership is created.');
            }

            if (! $user->isActive()) {
                throw new LogicException('Suspended users cannot be added to project membership.');
            }

            if ($project->owner_id === $user->id && $role !== ProjectMemberRole::Manager) {
                throw new LogicException('The project owner must be added as a project manager.');
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
        });
    }

    public function updateMemberRole(Project $project, User $user, UpdateProjectMemberData $data, ?User $actor = null): ProjectMember
    {
        return DB::transaction(function () use ($project, $user, $data, $actor): ProjectMember {
            $this->ensureMutable($project);
            $existing = $this->members->find($project, $user);
            if ($existing === null) {
                throw new LogicException('The user is not a project member.');
            }
            if ($project->owner_id === $user->id && $data->role !== ProjectMemberRole::Manager) {
                throw new LogicException('The project owner must remain a project manager.');
            }

            if ($existing->member_role === $data->role) {
                return $existing;
            }

            $membership = $this->members->updateRole($project, $user, $data->role);
            $this->activity->record('project.member_role_updated', $actor ?? $user, $project, [
                'project_id' => $project->id,
                'member_id' => $user->id,
                'member_name' => $user->name ?: $user->email,
                'old_member_role' => $existing->member_role->value,
                'new_member_role' => $data->role->value,
            ]);

            return $membership;
        });
    }

    public function removeMember(Project $project, User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($project, $user, $actor): void {
            $this->ensureMutable($project);
            $this->ensureExistingNonOwnerMembership($project, $user, 'removed');

            $openAssignments = $this->members->openAssignmentCount($project, $user);
            if ($openAssignments > 0) {
                throw new MemberHasOpenAssignments($openAssignments);
            }

            // Task watcher persistence is introduced by TF-606. There are no watcher rows to clean in this schema yet.
            $this->watchers->removeForProject($project, $user);
            $this->members->delete($project, $user);
            $this->activity->record('project.member_removed', $actor ?? $user, $project, [
                'project_id' => $project->id,
                'member_id' => $user->id,
                'member_name' => $user->name ?: $user->email,
            ]);
        });
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
        if (in_array($project->status, [ProjectStatus::Completed, ProjectStatus::Archived], true)) {
            throw new LogicException('Completed and archived projects are read-only.');
        }
    }

    private function ensureExistingNonOwnerMembership(Project $project, User $user, string $action): void
    {
        if ($project->owner_id === $user->id) {
            throw new LogicException("The project owner cannot be {$action} from project membership.");
        }

        if (! $this->isMember($project, $user)) {
            throw new LogicException('The user is not a project member.');
        }
    }
}
