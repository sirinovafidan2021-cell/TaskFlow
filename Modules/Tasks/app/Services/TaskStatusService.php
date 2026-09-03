<?php

namespace Modules\Tasks\Services;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\ChangeTaskStatusData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Exceptions\InvalidTaskStatusTransition;
use Modules\Tasks\Exceptions\TaskStatusConflict;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;

class TaskStatusService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity, private readonly TaskWatcherNotificationService $notifications, private readonly TaskRankService $ranks) {}

    /** @return list<TaskStatus> */
    public function availableStatuses(Task $task, User $actor): array
    {
        if (! $this->mayChange($task, $actor)) {
            return [];
        }

        $map = [
            TaskStatus::Backlog->value => [TaskStatus::Todo, TaskStatus::Cancelled],
            TaskStatus::Todo->value => [TaskStatus::Backlog, TaskStatus::InProgress, TaskStatus::Cancelled],
            TaskStatus::InProgress->value => [TaskStatus::Todo, TaskStatus::Review, TaskStatus::Cancelled],
            TaskStatus::Review->value => [TaskStatus::InProgress, TaskStatus::Done, TaskStatus::Cancelled],
            TaskStatus::Done->value => [TaskStatus::InProgress],
            TaskStatus::Cancelled->value => [TaskStatus::Backlog],
        ];

        if (in_array($task->status, [TaskStatus::Done, TaskStatus::Cancelled], true) && ! $this->mayReopen($task, $actor)) {
            return [];
        }

        return $map[$task->status->value] ?? [];
    }

    public function change(Task $task, ChangeTaskStatusData $data, User $actor): Task
    {
        return DB::transaction(function () use ($task, $data, $actor): Task {
            $task = $this->tasks->lockForUpdate($task);
            if ($task->version !== $data->expectedVersion) {
                throw new TaskStatusConflict('This task was changed by another request.');
            }
            if ($data->status === TaskStatus::Done && $this->tasks->hasOpenSubtasks($task)) {
                throw new InvalidTaskStatusTransition('A task with open subtasks cannot be completed.');
            }
            if (! in_array($data->status, $this->availableStatuses($task, $actor), true)) {
                throw new InvalidTaskStatusTransition('This task status transition is not allowed.');
            }

            $from = $task->status->value;
            if ($data->status === TaskStatus::InProgress && $task->started_at === null) {
                $task->started_at = now();
            }
            if ($data->status === TaskStatus::Done) {
                $task->completed_at = now();
            }
            if ($task->status === TaskStatus::Done && $data->status !== TaskStatus::Done) {
                $task->completed_at = null;
            }
            $task->status = $data->status;
            $task->version++;
            $task = $this->tasks->save($task);
            $task = $this->ranks->placeAtEnd($task);
            $this->activity->record('task.status_changed', $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'old' => $from, 'new' => $data->status->value, 'old_version' => $data->expectedVersion, 'new_version' => $task->version]);
            $this->notifications->notify($task->loadMissing('project'), $actor, 'task.status_changed');

            return $task;
        });
    }

    private function mayReopen(Task $task, User $actor): bool
    {
        return $actor->hasPermissionTo(PermissionName::TasksStatusChange->value) && $this->members->canManage($task->project, $actor);
    }

    private function mayChange(Task $task, User $actor): bool
    {
        return $task->project->status === ProjectStatus::Active
            && $actor->isActive()
            && $actor->hasPermissionTo(PermissionName::TasksStatusChange->value)
            && ($this->members->canManage($task->project, $actor)
                || ($task->assignee_id === $actor->id && $this->members->isMember($task->project, $actor)));
    }
}
