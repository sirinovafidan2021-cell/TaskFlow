<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\TaskLabel;

interface TaskLabelRepository
{
    /** @return Collection<int, TaskLabel> */
    public function forProject(Project $project): Collection;

    public function save(TaskLabel $label): TaskLabel;

    public function delete(TaskLabel $label): void;

    /** @param list<int> $ids */
    public function countForProject(Project $project, array $ids): int;
}
