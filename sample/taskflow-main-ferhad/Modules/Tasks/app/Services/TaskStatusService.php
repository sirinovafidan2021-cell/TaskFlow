<?php

namespace Modules\Tasks\Services;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Exceptions\InvalidTaskStatusTransition;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Activity\Services\ActivityRecorder;

class TaskStatusService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity) {}
    /** @return list<TaskStatus> */
    public function availableStatuses(Task $task, User $actor): array
    {
        $map = [TaskStatus::Todo->value => [TaskStatus::InProgress, TaskStatus::Cancelled], TaskStatus::InProgress->value => [TaskStatus::Todo, TaskStatus::Review, TaskStatus::Cancelled], TaskStatus::Review->value => [TaskStatus::InProgress, TaskStatus::Done]];
        $next = $map[$task->status->value] ?? [];
        if (in_array($task->status, [TaskStatus::Done, TaskStatus::Cancelled], true) && $this->mayReopen($task, $actor)) $next = [$task->status === TaskStatus::Done ? TaskStatus::InProgress : TaskStatus::Todo];
        return $next;
    }
    public function change(Task $task, TaskStatus $to, User $actor): Task
    {
        if (! in_array($to, $this->availableStatuses($task, $actor), true)) throw new InvalidTaskStatusTransition('This task status transition is not allowed.');
        return DB::transaction(function () use ($task, $to, $actor): Task {
            $from = $task->status->value;
            if ($to === TaskStatus::InProgress && $task->started_at === null) $task->started_at = now();
            if ($to === TaskStatus::Done) $task->completed_at = now();
            if ($task->status === TaskStatus::Done && $to !== TaskStatus::Done) $task->completed_at = null;
            $task->status = $to;
            $task = $this->tasks->save($task);
            $this->activity->record('task.status_changed', $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'old' => $from, 'new' => $to->value]);
            return $task;
        });
    }
    private function mayReopen(Task $task, User $actor): bool
    {
        return $actor->hasPermissionTo(PermissionName::TasksStatusChange->value) && $this->members->canManage($task->project, $actor);
    }
}
