<?php

namespace Modules\Projects\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;
use Modules\Projects\Data\ProjectFiltersData;

interface ProjectRepository
{
    public function findOrFail(int $id): Project;
    public function paginateFor(User $user, ProjectFiltersData $filters, int $perPage = 12): LengthAwarePaginator;

    public function save(Project $project): Project;

    public function lockForUpdate(Project $project): Project;

    public function hasAllocatedIssues(Project $project): bool;

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool;
}
