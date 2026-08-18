<?php

namespace Modules\Projects\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): Project;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Project;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project;

    public function delete(Project $project): void;
}
