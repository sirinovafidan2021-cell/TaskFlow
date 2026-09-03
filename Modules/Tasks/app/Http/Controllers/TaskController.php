<?php

namespace Modules\Tasks\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\CreateTaskData;
use Modules\Tasks\Data\ChangeTaskStatusData;
use Modules\Tasks\Data\TaskFiltersData;
use Modules\Tasks\Data\UpdateTaskData;
use Modules\Tasks\Data\SyncTaskLabelsData;
use Modules\Tasks\Data\ReorderTaskData;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;
use Modules\Tasks\Http\Requests\AssignTaskRequest;
use Modules\Tasks\Http\Requests\ChangeTaskStatusRequest;
use Modules\Tasks\Http\Requests\CreateTaskRequest;
use Modules\Tasks\Http\Requests\UpdateTaskRequest;
use Modules\Tasks\Http\Requests\SyncTaskLabelsRequest;
use Modules\Tasks\Http\Requests\ReorderTaskRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Repositories\TaskRepository;
use Modules\Tasks\Services\TaskAssignmentService;
use Modules\Tasks\Services\TaskService;
use Modules\Tasks\Services\TaskStatusService;
use Modules\Tasks\Services\TaskLabelService;
use Modules\Tasks\Services\TaskRankService;
use Spatie\Activitylog\Models\Activity;

class TaskController
{
    use AuthorizesRequests;

    public function __construct(private readonly TaskRepository $tasks, private readonly TaskService $taskService, private readonly TaskAssignmentService $assignments, private readonly TaskStatusService $statuses, private readonly ProjectMemberService $members, private readonly ActivityQueryService $activity, private readonly UserRepository $users, private readonly TaskLabelService $labels, private readonly TaskRankService $ranks) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        return view('tasks::index', ['tasks' => $this->tasks->paginateFor($request->user(), TaskFiltersData::fromArray($request->all())), 'statuses' => TaskStatus::cases(), 'types' => TaskType::cases(), 'priorities' => TaskPriority::cases(), 'projects' => $this->tasks->filterProjectsFor($request->user()), 'users' => $this->tasks->filterUsersFor($request->user()), 'labels' => $this->tasks->filterLabelsFor($request->user())]);
    }

    public function create(Project $project): View
    {
        $this->authorize('create', [Task::class, $project]);

        return view('tasks::create', ['project' => $project, 'memberships' => $this->members->memberships($project), 'priorities' => TaskPriority::cases(), 'types' => TaskType::cases(), 'parents' => $this->tasks->standardParentsForProject($project), 'labels' => $this->labels->forProject($project)]);
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
        $canViewActivity = request()->user()->can('viewAny', Activity::class);

        return view('tasks::show', ['task' => $task->load(['project', 'creator', 'assignee', 'labels', 'parent', 'subtasks', 'comments.user', 'attachments.uploader', 'attachments.media']), 'memberships' => $this->members->memberships($task->project), 'nextStatuses' => $this->statuses->availableStatuses($task, request()->user()), 'activities' => $canViewActivity ? $this->activity->recentForTask($task) : null, 'canViewActivity' => $canViewActivity]);
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks::edit', ['task' => $task->load(['project', 'labels']), 'types' => TaskType::cases(), 'parents' => $this->tasks->standardParentsForProject($task->project)->reject(fn (Task $parent): bool => $parent->id === $task->id), 'labels' => $this->labels->forProject($task->project)]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $this->taskService->update($task, UpdateTaskData::fromArray($request->validated()), $request->user());

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function syncLabels(SyncTaskLabelsRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $this->labels->sync($task->load('project'), SyncTaskLabelsData::fromArray($request->validated())->labelIds, $request->user());

        return redirect()->route('tasks.show', $task)->with('success', 'Task labels updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $this->taskService->delete($task, request()->user());

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function assign(AssignTaskRequest $request, Task $task): RedirectResponse
    {
        $assignee = $request->filled('assignee_id') ? $this->users->findOrFail($request->integer('assignee_id')) : null;
        $this->authorize('assign', [$task, $assignee]);
        $this->assignments->assign($task->load('project'), $assignee, $request->user());

        return back()->with('success', 'Task assignment updated.');
    }

    public function changeStatus(ChangeTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('changeStatus', $task);
        $this->statuses->change($task->load('project'), ChangeTaskStatusData::fromArray($request->validated()), $request->user());

        return back()->with('success', 'Task status updated.');
    }
    public function reorder(ReorderTaskRequest $request, Task $task): RedirectResponse { $this->authorize('reorder',$task); $this->ranks->reorder($task, ReorderTaskData::fromArray($request->validated()), $request->user()); return back()->with('success','Task reordered.'); }
}
