<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskAssignmentService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity, private readonly TaskWatcherRepository $watchers, private readonly TaskWatcherNotificationService $notifications) {}

    public function assign(Task $task, ?User $assignee, User $actor): Task
    {
        return DB::transaction(function () use ($task, $assignee, $actor): Task {
            if ($task->project->status !== ProjectStatus::Active) {
                throw new LogicException('Tasks can only be assigned in active projects.');
            }
            if (! $actor->isActive()) {
                throw new LogicException('Suspended users cannot change task assignment.');
            }
            if (! $this->members->canManage($task->project, $actor)
                && ($assignee?->id !== $actor->id || ! $this->members->isMember($task->project, $actor))) {
                throw new LogicException('Project members can only assign themselves.');
            }
            if ($assignee && (! $assignee->isActive() || ! $this->members->isMember($task->project, $assignee))) {
                throw new LogicException('The assignee must be an active project member.');
            }
            $oldAssignee = $task->assignee;
            if ($oldAssignee?->id === $assignee?->id) {
                return $task;
            }
            $task->assignee()->associate($assignee);
            $task->version++;
            $task = $this->tasks->save($task);
            if ($assignee !== null) {
                $this->watchers->ensureWatching($task, $assignee);
            }
            $this->activity->record('task.assigned', $actor, $task, [
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'old_assignee_id' => $oldAssignee?->id,
                'old_assignee_name' => $oldAssignee?->name ?: $oldAssignee?->email,
                'new_assignee_id' => $assignee?->id,
                'new_assignee_name' => $assignee?->name ?: $assignee?->email,
                'old' => ['assignee_id' => $oldAssignee?->id, 'assignee_name' => $oldAssignee?->name ?: $oldAssignee?->email],
                'new' => ['assignee_id' => $assignee?->id, 'assignee_name' => $assignee?->name ?: $assignee?->email],
            ]);
            $this->notifications->notify($task->loadMissing('project'), $actor, 'task.assigned');

            return $task;
        });
    }
}
