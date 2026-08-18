<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Activity\Services\ActivityRecorder;

class TaskService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity) {}

    public function create(User $actor, Project $project, CreateTaskData $data): Task
    {
        return DB::transaction(function () use ($actor, $project, $data): Task {
            if ($project->status !== ProjectStatus::Active) throw new LogicException('Tasks can only be created in active projects.');
            if (! $this->members->canManage($project, $actor)) throw new LogicException('The actor cannot create tasks in this project.');
            if ($data->assigneeId && ! $this->members->isMember($project, User::query()->findOrFail($data->assigneeId))) throw new LogicException('The assignee must be a project member.');

            $task = $this->tasks->save(new Task([
                'project_id' => $project->id, 'creator_id' => $actor->id, 'assignee_id' => $data->assigneeId,
                'title' => $data->title, 'description' => $data->description, 'status' => TaskStatus::Todo,
                'priority' => $data->priority, 'due_at' => $data->dueAt,
            ]));
            $task->number = 'TSK-'.str_pad((string) $task->id, 6, '0', STR_PAD_LEFT);

            $task = $this->tasks->save($task);
            $this->activity->record('task.created', $actor, $task, ['project_id' => $project->id, 'task_id' => $task->id, 'task_number' => $task->number, 'task_title' => $task->title]);
            return $task;
        });
    }

    public function update(Task $task, UpdateTaskData $data, User $actor): Task
    {
        return DB::transaction(function () use ($task, $data, $actor): Task {
            $task->fill(['title' => $data->title, 'description' => $data->description, 'priority' => $data->priority, 'due_at' => $data->dueAt]);
            $changed = array_keys($task->getDirty());
            $task = $this->tasks->save($task);
            if ($changed !== []) {
                $this->activity->record('task.updated', $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'changed' => $changed]);
            }
            return $task;
        });
    }

    public function delete(Task $task, User $actor): void { DB::transaction(function () use ($task, $actor): void { $this->tasks->delete($task); $this->activity->record('task.deleted', $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id]); }); }
}
