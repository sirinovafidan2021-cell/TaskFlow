<?php

namespace Modules\Projects\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Repositories\Contracts\ProjectMemberRepositoryInterface;

final class ProjectMemberService
{
    public function __construct(
        private ProjectMemberRepositoryInterface $projectMembers,
    ) {}

    /**
     * @return Collection<int, ProjectMember>
     */
    public function list(int $projectId): Collection
    {
        return $this->projectMembers->getByProjectId($projectId);
    }

    public function addMember(
        int $projectId,
        int $userId,
        string $memberRole,
    ): ProjectMember {
        if ($this->projectMembers->findByProjectAndUser($projectId, $userId) !== null) {
            throw ValidationException::withMessages([
                'user_id' => ['The user is already a member of this project.'],
            ]);
        }

        return $this->projectMembers->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'member_role' => $memberRole,
            'joined_at' => now(),
        ]);
    }

    public function removeMember(ProjectMember $projectMember): void
    {
        $this->projectMembers->delete($projectMember);
    }
}
