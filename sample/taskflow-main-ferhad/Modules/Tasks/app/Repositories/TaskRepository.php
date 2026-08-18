<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Models\Task;

interface TaskRepository
{
    public function paginate(TaskFiltersData $filters): LengthAwarePaginator;

    public function save(Task $task): Task;

    public function delete(Task $task): void;

    public function filterProjects(): Collection;

    public function filterUsers(): Collection;
}
