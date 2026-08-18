<?php

namespace Modules\Projects\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Http\Requests\Api\StoreProjectMemberRequest;
use Modules\Projects\Http\Resources\ProjectMemberResource;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;

class ProjectMemberController
{
    use AuthorizesRequests;

    public function __construct(private readonly ProjectMemberService $members) {}

    public function index(Project $project)
    {
        $this->authorize('manageMembers', $project);

        return ProjectMemberResource::collection($this->members->memberships($project))->additional(['success' => true, 'message' => 'Project members retrieved successfully.']);
    }

    public function store(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);
        $member = $this->members->addMember($project, User::query()->findOrFail($request->integer('user_id')), ProjectMemberRole::from($request->string('member_role')->toString()), actor: $request->user());

        return response()->json(['success' => true, 'message' => 'Project member added successfully.', 'data' => new ProjectMemberResource($member->load('user'))], 201);
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);
        $this->members->removeMember($project, $user, request()->user());

        return response()->json(['success' => true, 'message' => 'Project member removed successfully.']);
    }
}
