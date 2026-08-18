<?php

namespace Modules\Projects\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Models\ProjectMember;

interface ProjectMemberRepositoryInterface
{
    /**
     * @return Collection<int, ProjectMember>
     */
    public function getByProjectId(int $projectId): Collection;

    public function findByProjectAndUser(int $projectId, int $userId): ?ProjectMember;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ProjectMember;

    public function delete(ProjectMember $projectMember): void;
}
