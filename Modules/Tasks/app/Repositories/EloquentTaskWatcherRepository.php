<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Modules\Tasks\Models\Task;

class EloquentTaskWatcherRepository implements TaskWatcherRepository
{
    public function ensureWatching(Task $task, User $user): void
    {
        $task->watchers()->syncWithoutDetaching([$user->id]);
    }
}
