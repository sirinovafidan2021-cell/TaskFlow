<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;

class TaskAssignmentService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity) {}

    public function assign(Task $task, ?User $assignee, User $actor): Task
    {
        return DB::transaction(function () use ($task, $assignee, $actor): Task {
            if ($assignee && ! $this->members->isMember($task->project, $assignee)) {
                throw new LogicException('The assignee must belong to the project.');
            }
            $oldAssignee = $task->assignee;
            if ($oldAssignee?->id === $assignee?->id) {
                return $task;
            }
            $task->assignee()->associate($assignee);
            $task = $this->tasks->save($task);
            $this->activity->record('task.assigned', $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'old_assignee_id' => $oldAssignee?->id, 'old_assignee_name' => $oldAssignee?->name ?: $oldAssignee?->email, 'new_assignee_id' => $assignee?->id, 'new_assignee_name' => $assignee?->name ?: $assignee?->email]);

            return $task;
        });
    }
}
