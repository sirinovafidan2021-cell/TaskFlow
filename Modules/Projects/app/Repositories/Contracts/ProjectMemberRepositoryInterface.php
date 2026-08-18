<?php

namespace Modules\Projects\Repositories\Contracts;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;

interface ProjectMemberRepositoryInterface
{
    public function create(Project $project, User $user, ProjectMemberRole $role, DateTimeInterface $joinedAt): ProjectMember;

    public function delete(Project $project, User $user): void;

    public function exists(Project $project, User $user): bool;

    public function isManager(Project $project, User $user): bool;

    /** @return Collection<int, ProjectMember> */
    public function allForProject(Project $project): Collection;

    /** @return Collection<int, User> */
    public function availableUsersForProject(Project $project): Collection;
}
