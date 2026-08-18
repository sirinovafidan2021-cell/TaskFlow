<?php

namespace Modules\Tasks\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Projects\Models\Project;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Http\Requests\Api\StoreTaskRequest;
use Modules\Tasks\Http\Requests\Api\TaskIndexRequest;
use Modules\Tasks\Http\Requests\Api\UpdateTaskRequest;
use Modules\Tasks\Http\Resources\TaskResource;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\Contracts\TaskRepositoryInterface;
use Modules\Tasks\Services\TaskService;

class TaskController
{
    use AuthorizesRequests;

    public function __construct(private readonly TaskRepositoryInterface $tasks, private readonly TaskService $service) {}

    public function index(TaskIndexRequest $request)
    {
        $this->authorize('viewAny', Task::class);

        return TaskResource::collection($this->tasks->paginate(TaskFiltersData::fromArray($request->validated()), $request->user()))->additional(['success' => true, 'message' => 'Tasks retrieved successfully.']);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(['success' => true, 'message' => 'Task retrieved successfully.', 'data' => new TaskResource($task->load(['project', 'creator', 'assignee']))]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $project = Project::query()->findOrFail($request->integer('project_id'));
        $this->authorize('create', [Task::class, $project]);
        $task = $this->service->create($request->user(), $project, CreateTaskData::fromArray($project->id, $request->validated()));

        return response()->json(['success' => true, 'message' => 'Task created successfully.', 'data' => new TaskResource($task->load(['project', 'creator', 'assignee']))], 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $task = $this->service->update($task, UpdateTaskData::fromArray($request->validated()), $request->user());

        return response()->json(['success' => true, 'message' => 'Task updated successfully.', 'data' => new TaskResource($task->load(['project', 'creator', 'assignee']))]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->service->delete($task, request()->user());

        return response()->json(['success' => true, 'message' => 'Task deleted successfully.']);
    }
}
