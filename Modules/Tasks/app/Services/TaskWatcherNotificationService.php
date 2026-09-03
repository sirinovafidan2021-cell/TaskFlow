<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use App\Notifications\TaskWatcherNotification;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskWatcherNotificationService
{
    public function __construct(private readonly TaskWatcherRepository $watchers) {}
    public function notify(Task $task, User $actor, string $event): void { foreach ($this->watchers->eligibleWatchers($task) as $watcher) if ($watcher->id !== $actor->id) $watcher->notify(new TaskWatcherNotification($task, $actor, $event)); }
}
