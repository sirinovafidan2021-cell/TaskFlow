<?php

namespace Modules\Tasks\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Models\Task;

interface TaskRepositoryInterface
{
    public function paginate(TaskFiltersData $filters, User $actor): LengthAwarePaginator;

    public function save(Task $task): Task;

    public function delete(Task $task): void;

    /** @return Collection<int, mixed> */
    public function filterProjects(User $actor): Collection;

    /** @return Collection<int, User> */
    public function filterUsers(User $actor): Collection;
}
