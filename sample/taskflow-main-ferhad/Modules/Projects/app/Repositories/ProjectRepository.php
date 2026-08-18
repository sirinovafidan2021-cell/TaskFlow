<?php

namespace Modules\Projects\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;

interface ProjectRepository
{
    public function paginate(?string $search, ?string $status): LengthAwarePaginator;

    public function save(Project $project): Project;

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool;
}
