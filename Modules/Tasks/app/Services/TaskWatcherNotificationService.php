<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use App\Notifications\TaskWatcherNotification;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskWatcherNotificationService
{
    public function __construct(private readonly TaskWatcherRepository $watchers) {}
    /** @return \Illuminate\Support\Collection<int, User> */
    public function recipients(Task $task, User $actor): \Illuminate\Support\Collection { return $this->watchers->eligibleWatchers($task)->reject(fn (User $watcher): bool => $watcher->id === $actor->id)->values(); }
    public function notify(Task $task, User $actor, ActivityEvent $event): void { $this->recipients($task, $actor)->each(fn (User $watcher) => $watcher->notify(new TaskWatcherNotification($task, $actor, $event->value))); }
}
