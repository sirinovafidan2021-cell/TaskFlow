<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Activity\Services\ActivityQueryService;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Http\Requests\StoreProjectRequest;
use Modules\Projects\Http\Requests\UpdateProjectRequest;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\ProjectRepository;
use Modules\Projects\Services\ProjectService;
use Spatie\Activitylog\Models\Activity;

class ProjectController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectService $projectService,
        private readonly ActivityQueryService $activity,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        return view('projects::index', [
            'projects' => $this->projects->paginateFor(
                $request->user(),
                $request->string('q')->trim()->toString(),
                $request->string('status')->toString(),
            ),
            'statuses' => ProjectStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects::create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create(
            $request->user(),
            ProjectData::fromArray($request->validated()),
        );

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);
        $canViewActivity = request()->user()->can('viewAny', Activity::class);

        return view('projects::show', [
            'project' => $project->load('owner')->loadCount('memberships'),
            'activities' => $canViewActivity ? $this->activity->recentForProject($project) : null,
            'canViewActivity' => $canViewActivity,
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects::edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update($project, ProjectData::fromArray($request->validated()), $request->user());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function archive(Project $project): RedirectResponse
    {
        $this->authorize('archive', $project);

        $this->projectService->archive($project, request()->user());

        return redirect()->route('projects.index')
            ->with('success', 'Project archived successfully.');
    }

    public function activate(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->projectService->activate($project, request()->user());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project activated successfully.');
    }
}
