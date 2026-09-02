<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Modules\Tasks\Models\Task;

interface TaskWatcherRepository
{
    public function ensureWatching(Task $task, User $user): void;
}
