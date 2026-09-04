<?php

namespace Modules\Activity\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Spatie\Activitylog\Models\Activity;

class ActivityController
{
    use AuthorizesRequests;

    public function __construct(private readonly ActivityQueryService $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $filters = $request->only(['event', 'project', 'task', 'actor', 'date_from', 'date_to']);

        return view('activity::index', [
            'activities' => $this->activity->paginate($request->user(), $filters),
            'filters' => $filters,
            'options' => $this->activity->filterOptions($request->user()),
        ]);
    }

    public function forProject(Request $request, Project $project): View
    {
        $this->authorize('viewAny', Activity::class);
        $this->authorize('view', $project);
        return view('activity::index', ['activities' => $this->activity->paginate($request->user(), [...$request->only(['event', 'actor', 'date_from', 'date_to']), 'project' => $project->id]), 'filters' => ['project' => $project->id], 'options' => $this->activity->filterOptions($request->user())]);
    }

    public function forTask(Request $request, Task $task): View
    {
        $this->authorize('viewAny', Activity::class);
        $this->authorize('view', $task);
        return view('activity::index', ['activities' => $this->activity->paginate($request->user(), [...$request->only(['event', 'actor', 'date_from', 'date_to']), 'task' => $task->id]), 'filters' => ['task' => $task->id], 'options' => $this->activity->filterOptions($request->user())]);
    }
}
