<?php

namespace Modules\Projects\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Http\Requests\Web\AddProjectMemberRequest;
use Modules\Projects\Http\Requests\Web\CreateProjectRequest;
use Modules\Projects\Http\Requests\Web\UpdateProjectRequest;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectMember;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Projects\Services\ProjectService;

final class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projects,
        private ProjectMemberService $projectMembers,
    ) {}

    public function index(Request $request): View
    {
        $projects = $this->projects->paginateForUser(
            $request->user(),
            15,
        );

        return view('projects::projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects::projects.show', compact('project'));
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects::projects.create');
    }

    public function store(CreateProjectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $user = $request->user();

        $data = new ProjectData(
            name: $request->string('name')->toString(),
            slug: $request->string('slug')->toString(),
            description: $request->input('description'),
            status: $request->string('status')->toString(),
            startsAt: $request->input('starts_at'),
            dueAt: $request->input('due_at'),
            ownerId: $user->id,
        );

        $project = $this->projects->create($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects::projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = new ProjectData(
            name: $request->string('name')->toString(),
            slug: $request->string('slug')->toString(),
            description: $request->input('description'),
            status: $request->string('status')->toString(),
            startsAt: $request->input('starts_at'),
            dueAt: $request->input('due_at'),
            ownerId: $project->owner_id,
        );

        $this->projects->update($project, $data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function archive(Project $project): RedirectResponse
    {
        Gate::authorize('archive', $project);

        $this->projects->archive($project);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project archived successfully.');
    }

    public function members(Project $project): View
    {
        Gate::authorize('view', $project);

        $members = $this->projectMembers->list($project->id);

        return view('projects::projects.members', compact(
            'project',
            'members',
        ));
    }

    public function addMember(AddProjectMemberRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        $this->projectMembers->addMember(
            projectId: $project->id,
            userId: $request->integer('user_id'),
            memberRole: $request->string('member_role')->toString(),
        );

        return redirect()
            ->route('projects.members', $project)
            ->with('success', 'Member added successfully.');
    }

    public function removeMember(Project $project, ProjectMember $projectMember): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        if ($projectMember->project_id !== $project->id) {
            abort(404);
        }

        $this->projectMembers->removeMember($projectMember);

        return redirect()
            ->route('projects.members', $project)
            ->with('success', 'Member removed successfully.');
    }
}
