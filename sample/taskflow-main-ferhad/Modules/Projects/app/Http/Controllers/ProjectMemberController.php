<?php

namespace Modules\Projects\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Http\Requests\StoreProjectMemberRequest;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;

class ProjectMemberController
{
    use AuthorizesRequests;

    public function __construct(private readonly ProjectMemberService $members)
    {
    }

    public function index(Project $project): View
    {
        $this->authorize('manageMembers', $project);

        return view('projects::members.index', [
            'project' => $project->load('owner'),
            'memberships' => $this->members->memberships($project),
            'availableUsers' => $this->members->availableUsers($project),
            'roles' => ProjectMemberRole::cases(),
        ]);
    }

    public function store(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $this->members->addMember(
            $project,
            User::query()->findOrFail($request->integer('user_id')),
            ProjectMemberRole::from($request->string('member_role')->toString()),
            actor: $request->user(),
        );

        return back()->with('success', 'Project member added.');
    }

    public function destroy(Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $this->members->removeMember($project, $user, request()->user());

        return back()->with('success', 'Project member removed.');
    }
}
