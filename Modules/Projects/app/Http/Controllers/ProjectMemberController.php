<?php

namespace Modules\Projects\Http\Controllers;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Http\Requests\StoreProjectMemberRequest;
use Modules\Projects\Http\Requests\UpdateProjectMemberRequest;
use Modules\Projects\Data\UpdateProjectMemberData;
use Modules\Projects\Exceptions\MemberHasOpenAssignments;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;

class ProjectMemberController
{
    use AuthorizesRequests;

    public function __construct(private readonly ProjectMemberService $members, private readonly UserRepository $users) {}

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
            $this->users->findOrFail($request->integer('user_id')),
            ProjectMemberRole::from($request->string('member_role')->toString()),
            actor: $request->user(),
        );

        return back()->with('success', 'Project member added.');
    }

    public function destroy(Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        try {
            $this->members->removeMember($project, $user, request()->user());
        } catch (MemberHasOpenAssignments $exception) {
            return back()->withErrors([
                'user_id' => "This member has {$exception->count} open assignment(s). Reassign or unassign them before removal.",
            ]);
        }

        return back()->with('success', 'Project member removed.');
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $this->members->updateMemberRole(
            $project,
            $user,
            new UpdateProjectMemberData(ProjectMemberRole::from($request->string('member_role')->toString())),
            $request->user(),
        );

        return back()->with('success', 'Project member role updated.');
    }
}
