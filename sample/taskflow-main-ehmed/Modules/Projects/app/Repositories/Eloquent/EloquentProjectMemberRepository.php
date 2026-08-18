<?php

namespace Modules\Projects\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Repositories\Contracts\ProjectMemberRepositoryInterface;

final class EloquentProjectMemberRepository implements ProjectMemberRepositoryInterface
{
    /**
     * @return Collection<int, ProjectMember>
     */
    public function getByProjectId(int $projectId): Collection
    {
        return ProjectMember::query()
            ->with('user')
            ->where('project_id', $projectId)
            ->get();
    }

    public function findByProjectAndUser(int $projectId, int $userId): ?ProjectMember
    {
        return ProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ProjectMember
    {
        return ProjectMember::query()->create($attributes);
    }

    public function delete(ProjectMember $projectMember): void
    {
        $projectMember->delete();
    }
}
