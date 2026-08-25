<?php

namespace Modules\Projects\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;

interface ProjectMemberRepository
{
    public function create(Project $project, User $user, ProjectMemberRole $role, \DateTimeInterface $joinedAt): ProjectMember;

    public function delete(Project $project, User $user): void;

    public function exists(Project $project, User $user): bool;

    public function isManager(Project $project, User $user): bool;

    /** @return Collection<int, ProjectMember> */
    public function allForProject(Project $project): Collection;

    public function paginateForProject(Project $project, int $perPage): LengthAwarePaginator;

    /** @return Collection<int, User> */
    public function availableUsers(Project $project): Collection;
}
