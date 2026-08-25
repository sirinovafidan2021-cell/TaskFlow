<?php

namespace Modules\Projects\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;

interface ProjectRepository
{
    public function paginateFor(User $user, ?string $search, ?string $status, int $perPage = 12): LengthAwarePaginator;

    public function save(Project $project): Project;

    public function slugExists(string $slug, ?int $excludingProjectId = null): bool;
}
