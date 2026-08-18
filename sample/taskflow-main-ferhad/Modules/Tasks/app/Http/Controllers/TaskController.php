<?php

namespace Modules\Tasks\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Http\Requests\AssignTaskRequest;
use Modules\Tasks\Http\Requests\ChangeTaskStatusRequest;
use Modules\Tasks\Http\Requests\CreateTaskRequest;
use Modules\Tasks\Http\Requests\UpdateTaskRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Services\TaskAssignmentService;
use Modules\Tasks\Services\TaskService;
use Modules\Tasks\Services\TaskStatusService;
use Spatie\Activitylog\Models\Activity;

class TaskController
{
    use AuthorizesRequests;

    public function __construct(private readonly TaskRepository $tasks, private readonly TaskService $taskService, private readonly TaskAssignmentService $assignments, private readonly TaskStatusService $statuses, private readonly ProjectMemberService $members, private readonly ActivityQueryService $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        return view('tasks::index', ['tasks' => $this->tasks->paginate(TaskFiltersData::fromArray($request->all())), 'statuses' => TaskStatus::cases(), 'priorities' => TaskPriority::cases(), 'projects' => $this->tasks->filterProjects(), 'users' => $this->tasks->filterUsers()]);
    }

    public function create(Project $project): View
    {
        $this->authorize('create', [Task::class, $project]);

        return view('tasks::create', ['project' => $project, 'memberships' => $this->members->memberships($project), 'priorities' => TaskPriority::cases()]);
    }

    public function store(CreateTaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);
        $task = $this->taskService->create($request->user(), $project, CreateTaskData::fromArray($project->id, $request->validated()));

        return redirect()->route('tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks::show', ['task' => $task->load(['project', 'creator', 'assignee', 'comments.user', 'attachments.uploader']), 'memberships' => $this->members->memberships($task->project), 'nextStatuses' => $this->statuses->availableStatuses($task, request()->user()), 'activities' => $this->activity->recentForTask($task), 'canViewActivity' => request()->user()->can('viewAny', Activity::class)]);
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks::edit', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $this->taskService->update($task, UpdateTaskData::fromArray($request->validated()), $request->user());

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $this->taskService->delete($task, request()->user());

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function assign(AssignTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('assign', $task);
        $this->assignments->assign($task->load('project'), $request->filled('assignee_id') ? User::query()->findOrFail($request->integer('assignee_id')) : null, $request->user());

        return back()->with('success', 'Task assignment updated.');
    }

    public function changeStatus(ChangeTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('changeStatus', $task);
        $this->statuses->change($task->load('project'), TaskStatus::from($request->string('status')->toString()), $request->user());

        return back()->with('success', 'Task status updated.');
    }
}
