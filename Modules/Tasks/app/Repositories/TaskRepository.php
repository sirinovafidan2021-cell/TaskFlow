<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Models\Task;
use Modules\Projects\Models\Project;

interface TaskRepository
{
    public function paginateFor(User $user, TaskFiltersData $filters, int $perPage = 12): LengthAwarePaginator;

    public function save(Task $task): Task;

    public function findOrFail(int $id): Task;

    public function lockForUpdate(Task $task): Task;

    public function hasOpenSubtasks(Task $task): bool;

    public function hasSubtasks(Task $task): bool;

    public function standardParentsForProject(Project $project): Collection;

    public function delete(Task $task): void;

    public function unassignOpenWorkFor(User $user): int;

    public function filterProjectsFor(User $user): Collection;

    public function filterUsersFor(User $user): Collection;

    public function filterLabelsFor(User $user): Collection;
}
