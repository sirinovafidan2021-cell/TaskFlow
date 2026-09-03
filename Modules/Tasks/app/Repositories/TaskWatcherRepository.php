<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;

interface TaskWatcherRepository
{
    public function ensureWatching(Task $task, User $user): void;
    public function removeWatching(Task $task, User $user): void;
    public function removeForProject(Project $project, User $user): int;
    public function removeForUser(User $user): int;
    /** @return Collection<int, User> */
    public function eligibleWatchers(Task $task): Collection;
}
