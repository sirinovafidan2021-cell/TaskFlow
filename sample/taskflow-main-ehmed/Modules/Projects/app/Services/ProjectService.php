<?php

namespace Modules\Projects\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;
use Modules\Projects\Data\ProjectData;
use App\Models\User;

final class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {}

    public function paginateForUser(User $user, int $perPage,): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $perPage);
    }

    public function findOrFail(int $id): Project
    {
        return $this->projects->findOrFail($id);
    }

    public function create(ProjectData $data): Project
    {
        return $this->projects->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'status' => $data->status,
            'owner_id' => $data->ownerId,
            'starts_at' => $data->startsAt,
            'due_at' => $data->dueAt,
        ]);
    }

    public function update(Project $project, ProjectData $data): Project
    {
        return $this->projects->update($project, [
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'status' => $data->status,
            'owner_id' => $data->ownerId,
            'starts_at' => $data->startsAt,
            'due_at' => $data->dueAt,
        ]);
    }

    public function archive(Project $project): Project
    {
        return $this->projects->update($project, [
            'status' => 'archived',
        ]);
    }

    public function delete(Project $project): void
    {
        $this->projects->delete($project);
    }
}
