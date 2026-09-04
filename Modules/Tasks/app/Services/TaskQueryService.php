<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Repositories\TaskRepository;

class TaskQueryService
{
    public function __construct(private readonly TaskRepository $tasks) {}

    public function paginateFor(User $actor, TaskFiltersData $filters): LengthAwarePaginator
    {
        return $this->tasks->paginateFor($actor, $filters);
    }

    public function filterOptionsFor(User $actor): array
    {
        return [
            'projects' => $this->tasks->filterProjectsFor($actor),
            'assignees' => $this->tasks->filterUsersFor($actor),
            'reporters' => $this->tasks->filterReportersFor($actor),
            'parents' => $this->tasks->filterParentsFor($actor),
            'labels' => $this->tasks->filterLabelsFor($actor),
        ];
    }
}
