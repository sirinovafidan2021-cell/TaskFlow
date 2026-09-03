<?php

namespace Modules\Tasks\Http\Controllers\Api\V1;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\ProjectRepository;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\ChangeTaskStatusData;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Data\SyncTaskLabelsData;
use Modules\Tasks\Data\ReorderTaskData;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Http\Requests\Api\V1\StoreTaskRequest;
use Modules\Tasks\Http\Requests\Api\V1\TaskIndexRequest;
use Modules\Tasks\Http\Requests\AssignTaskRequest;
use Modules\Tasks\Http\Requests\ChangeTaskStatusRequest;
use Modules\Tasks\Http\Requests\UpdateTaskRequest;
use Modules\Tasks\Http\Requests\SyncTaskLabelsRequest;
use Modules\Tasks\Http\Requests\ReorderTaskRequest;
use Modules\Tasks\Http\Resources\TaskResource;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Services\TaskAssignmentService;
use Modules\Tasks\Services\TaskService;
use Modules\Tasks\Services\TaskStatusService;
use Modules\Tasks\Services\TaskRankService;

class TaskController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskService $taskService,
        private readonly TaskAssignmentService $assignments,
        private readonly TaskStatusService $statuses,
        private readonly UserRepository $users,
        private readonly ProjectRepository $projects,
        private readonly TaskRankService $ranks,
    ) {}

    public function index(TaskIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        return TaskResource::collection($this->tasks->paginateFor(
            $request->user(),
            TaskFiltersData::fromArray($request->validated()),
            $request->integer('per_page', 12),
        ));
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load(['project', 'creator', 'assignee', 'labels', 'parent', 'subtasks']));
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $project = $this->projects->findOrFail($data['project_id']);
        $this->authorize('create', [Task::class, $project]);

        $task = $this->taskService->create(
            $request->user(),
            $project,
            CreateTaskData::fromArray($project->id, $data),
        );

        return (new TaskResource($task->load(['project', 'creator', 'assignee', 'labels', 'parent', 'subtasks'])))->response()->setStatusCode(201);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, UpdateTaskData::fromArray($request->validated()), $request->user());

        return new TaskResource($task->load(['project', 'creator', 'assignee', 'labels', 'parent', 'subtasks']));
    }

    public function syncLabels(SyncTaskLabelsRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);
        $task = $this->taskService->syncLabels($task->load('project'), SyncTaskLabelsData::fromArray($request->validated())->labelIds, $request->user());

        return new TaskResource($task->load(['project', 'creator', 'assignee', 'labels']));
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->taskService->delete($task, request()->user());

        return response()->json(null, 204);
    }

    public function assign(AssignTaskRequest $request, Task $task): TaskResource
    {
        $assignee = $request->filled('assignee_id')
            ? $this->users->findOrFail($request->integer('assignee_id'))
            : null;
        $this->authorize('assign', [$task, $assignee]);
        $task = $this->assignments->assign($task->load('project'), $assignee, $request->user());

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }

    public function changeStatus(ChangeTaskStatusRequest $request, Task $task): TaskResource
    {
        $this->authorize('changeStatus', $task);

        $task = $this->statuses->change(
            $task->load('project'),
            ChangeTaskStatusData::fromArray($request->validated()),
            $request->user(),
        );

        return new TaskResource($task->load(['project', 'creator', 'assignee']));
    }
    public function reorder(ReorderTaskRequest $request, Task $task): TaskResource { $this->authorize('reorder',$task); return new TaskResource($this->ranks->reorder($task, ReorderTaskData::fromArray($request->validated()), $request->user())->load(['project','creator','assignee'])); }
}
