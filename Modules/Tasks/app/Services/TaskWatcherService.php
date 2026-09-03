<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskWatcherService
{
    public function __construct(private readonly TaskWatcherRepository $watchers, private readonly ProjectMemberService $members) {}
    public function watch(Task $task, User $user, User $actor): void { $this->change($task, $user, $actor, true); }
    public function unwatch(Task $task, User $user, User $actor): void { $this->change($task, $user, $actor, false); }
    private function change(Task $task, User $user, User $actor, bool $watch): void
    {
        DB::transaction(function () use ($task, $user, $actor, $watch): void {
            $project = $task->loadMissing('project')->project;
            if ($project->status !== ProjectStatus::Active || ! $actor->isActive() || ! $user->isActive() || ! $this->member($project, $user) || ! $this->member($project, $actor)) throw ValidationException::withMessages(['user_id' => ['Only active project members can watch this task.']]);
            if ($actor->id !== $user->id && ! $this->members->canManage($project, $actor)) throw new LogicException('Only project managers can manage another user\'s watcher state.');
            $watch ? $this->watchers->ensureWatching($task, $user) : $this->watchers->removeWatching($task, $user);
        });
    }
    private function member($project, User $user): bool { return $this->members->canManage($project, $user) || $this->members->isMember($project, $user); }
}
