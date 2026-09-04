<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectService;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Repositories\TaskWatcherRepository;

class TaskService
{
    public function __construct(private readonly TaskRepository $tasks, private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity, private readonly ProjectService $projects, private readonly UserRepository $users, private readonly TaskLabelService $labels, private readonly TaskWatcherRepository $watchers, private readonly TaskRankService $ranks) {}

    public function create(User $actor, Project $project, CreateTaskData $data): Task
    {
        return DB::transaction(function () use ($actor, $project, $data): Task {
            if ($project->status !== ProjectStatus::Active) {
                throw new LogicException('Tasks can only be created in active projects.');
            }
            if (! $this->members->canManage($project, $actor) && ! $this->members->isMember($project, $actor)) {
                throw new LogicException('The actor cannot create tasks in this project.');
            }
            if ($data->assigneeId) {
                $assignee = $this->users->findOrFail($data->assigneeId);
                if (! $assignee->isActive() || ! $this->members->isMember($project, $assignee)) {
                    throw new LogicException('The assignee must be an active project member.');
                }
                if (! $this->members->canManage($project, $actor) && $assignee->id !== $actor->id) {
                    throw new LogicException('Project members can only assign themselves.');
                }
            }

            $parent = $this->validateParent($project, $data->type, $data->parentId);
            $allocatedIssue = $this->projects->allocateIssueNumber($project);
            $task = $this->tasks->save(new Task([
                'number' => $allocatedIssue->displayKey,
                'issue_number' => $allocatedIssue->issueNumber,
                'version' => 1,
                'project_id' => $project->id, 'creator_id' => $actor->id, 'assignee_id' => $data->assigneeId, 'type' => $data->type, 'parent_id' => $parent?->id,
                'title' => $data->title, 'description' => $data->description, 'status' => TaskStatus::Backlog,
                'priority' => $data->priority, 'due_at' => $data->dueAt,
            ]));
            $task = $this->ranks->placeAtEnd($task);
            if ($data->labelIds !== []) { $this->labels->sync($task, $data->labelIds, $actor); }
            $this->watchers->ensureWatching($task, $actor);
            if ($data->assigneeId && $data->assigneeId !== $actor->id) $this->watchers->ensureWatching($task, $assignee);
            $this->activity->record(ActivityEvent::TaskCreated, $actor, $task, ['project_id' => $project->id, 'task_id' => $task->id, 'task_number' => $task->number, 'task_title' => $task->title]);

            return $task;
        });
    }

    public function update(Task $task, UpdateTaskData $data, User $actor): Task
    {
        return DB::transaction(function () use ($task, $data, $actor): Task {
            $this->ensureUpdateAllowed($task, $actor);
            $type = $data->type ?? $task->type;
            $parentId = $data->parentProvided ? $data->parentId : $task->parent_id;
            if ($type === TaskType::Subtask && $this->tasks->hasSubtasks($task)) {
                throw new LogicException('A task with subtasks cannot become a subtask.');
            }
            $parent = $this->validateParent($task->project, $type, $parentId, $task);
            $task->fill(['title' => $data->title, 'description' => $data->description, 'priority' => $data->priority, 'due_at' => $data->dueAt, 'type' => $type, 'parent_id' => $parent?->id]);
            $changed = array_keys($task->getDirty());
            $old = $this->safeChangedValues($task, $changed, true);
            $new = $this->safeChangedValues($task, $changed, false);
            if ($changed !== []) {
                $task->version++;
                $task = $this->tasks->save($task);
                $this->activity->record(ActivityEvent::TaskUpdated, $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'changed' => $changed, 'old' => $old, 'new' => $new]);
            }

            if ($changed === []) {
                $task = $this->tasks->save($task);
            }
            if ($data->labelsProvided) { $this->labels->sync($task, $data->labelIds, $actor); }

            return $task;
        });
    }

    /** @param list<int> $labelIds */
    public function syncLabels(Task $task, array $labelIds, User $actor): Task
    {
        $this->ensureUpdateAllowed($task, $actor);

        return $this->labels->sync($task, $labelIds, $actor);
    }

    public function delete(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor): void {
            if ($task->project->status !== ProjectStatus::Active || ! $this->members->canManage($task->project, $actor)) {
                throw new LogicException('The actor cannot delete this task.');
            }
            $this->tasks->delete($task);
            $this->activity->record(ActivityEvent::TaskDeleted, $actor, $task, ['project_id' => $task->project_id, 'task_id' => $task->id, 'task_number' => $task->number, 'task_title' => $task->title]);
        });
    }

    private function ensureUpdateAllowed(Task $task, User $actor): void
    {
        if ($task->project->status !== ProjectStatus::Active) {
            throw new LogicException('Tasks can only be changed in active projects.');
        }

        if ($this->members->canManage($task->project, $actor)) {
            return;
        }

        if ($this->members->isMember($task->project, $actor)
            && $task->creator_id === $actor->id
            && in_array($task->status, [TaskStatus::Backlog, TaskStatus::Todo], true)) {
            return;
        }

        throw new LogicException('The actor cannot update this task.');
    }

    /** @return array<string, mixed> */
    private function safeChangedValues(Task $task, array $changed, bool $old): array
    {
        $values = [];
        foreach ($changed as $attribute) {
            if ($attribute === 'description') {
                $values['description_changed'] = true;

                continue;
            }

            $value = $old ? $task->getOriginal($attribute) : $task->getAttribute($attribute);
            $values[$attribute] = $this->activityValue($value);
        }

        return $values;
    }

    private function activityValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    private function validateParent(Project $project, TaskType $type, ?int $parentId, ?Task $task = null): ?Task
    {
        if ($type !== TaskType::Subtask) {
            if ($parentId !== null) {
                throw new LogicException('Only subtasks may have a parent.');
            }

            return null;
        }

        if ($parentId === null) {
            throw new LogicException('A subtask requires a parent task.');
        }

        $parent = $this->tasks->findOrFail($parentId);
        if ($parent->id === $task?->id || $parent->project_id !== $project->id) {
            throw new LogicException('The subtask parent must belong to the same project.');
        }
        if ($parent->type === TaskType::Subtask) {
            throw new LogicException('A subtask cannot have another subtask as parent.');
        }

        return $parent;
    }
}
