<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskWatcherService
{
    public function __construct(private readonly TaskWatcherRepository $watchers, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity) {}
    public function watch(Task $task, User $user, User $actor): void { $this->change($task, $user, $actor, true); }
    public function unwatch(Task $task, User $user, User $actor): void { $this->change($task, $user, $actor, false); }
    private function change(Task $task, User $user, User $actor, bool $watch): void
    {
        DB::transaction(function () use ($task, $user, $actor, $watch): void {
            $project = $task->loadMissing('project')->project;
            if ($project->status !== ProjectStatus::Active || ! $actor->isActive() || ! $user->isActive() || ! $this->member($project, $user) || ! $this->member($project, $actor)) throw ValidationException::withMessages(['user_id' => ['Only active project members can watch this task.']]);
            if ($actor->id !== $user->id && ! $this->members->canManage($project, $actor)) throw new LogicException('Only project managers can manage another user\'s watcher state.');
            $exists = $task->watchers()->whereKey($user->id)->exists();
            if ($watch && ! $exists) { $this->watchers->ensureWatching($task, $user); $this->activity->record(ActivityEvent::WatcherAdded, $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'watcher_id' => $user->id]); }
            if (! $watch && $exists) { $this->watchers->removeWatching($task, $user); $this->activity->record(ActivityEvent::WatcherRemoved, $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'watcher_id' => $user->id]); }
        });
    }
    private function member($project, User $user): bool { return $this->members->canManage($project, $user) || $this->members->isMember($project, $user); }
}
