<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Models\Task;

interface TaskRepository
{
    public function paginateFor(User $user, TaskFiltersData $filters, int $perPage = 12): LengthAwarePaginator;

    public function save(Task $task): Task;

    public function delete(Task $task): void;

    public function unassignOpenWorkFor(User $user): int;

    public function filterProjectsFor(User $user): Collection;

    public function filterUsersFor(User $user): Collection;
}
