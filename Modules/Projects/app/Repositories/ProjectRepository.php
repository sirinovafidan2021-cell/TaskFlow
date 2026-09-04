<?php

namespace Modules\Projects\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Projects\Data\ProjectFiltersData;

interface ProjectRepository
{
    public function findOrFail(int $id): Project;
    public function paginateFor(User $user, ProjectFiltersData $filters, int $perPage = 12): LengthAwarePaginator;

    public function visibleQueryFor(User $user): Builder;

    public function detailFor(User $user, Project $project): Project;

    /** @return Collection<int, Project> */
    public function activeForTaskCreation(User $user): Collection;

    public function save(Project $project): Project;

    public function lockForUpdate(Project $project): Project;

    public function hasAllocatedIssues(Project $project): bool;

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool;
}
