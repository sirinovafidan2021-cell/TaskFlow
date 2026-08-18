<?php

namespace Modules\Projects\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Data\ProjectFiltersData;
use Modules\Projects\Models\Project;

interface ProjectRepositoryInterface
{
    public function paginateVisibleTo(User $actor, ProjectFiltersData $filters): LengthAwarePaginator;

    public function save(Project $project): Project;

    public function delete(Project $project): void;

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool;
}
