<?php

namespace Modules\Projects\Http\Controllers\Api\V1;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Projects\Enums\ProjectMemberRole;
use Modules\Projects\Http\Requests\Api\V1\ProjectMemberIndexRequest;
use Modules\Projects\Http\Requests\StoreProjectMemberRequest;
use Modules\Projects\Http\Requests\UpdateProjectMemberRequest;
use Modules\Projects\Data\UpdateProjectMemberData;
use Modules\Projects\Http\Resources\ProjectMemberResource;
use Modules\Projects\Models\Project;
use Modules\Projects\Services\ProjectMemberService;

class ProjectMemberController
{
    use AuthorizesRequests;

    public function __construct(private readonly ProjectMemberService $members, private readonly UserRepository $users) {}

    public function index(ProjectMemberIndexRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return ProjectMemberResource::collection(
            $this->members->paginateMemberships($project, $request->integer('per_page', 20)),
        );
    }

    public function store(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $membership = $this->members->addMember(
            $project,
            $this->users->findOrFail($request->integer('user_id')),
            ProjectMemberRole::from($request->string('member_role')->toString()),
            actor: $request->user(),
        );

        return (new ProjectMemberResource($membership->load('user')))->response()->setStatusCode(201);
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);
        $this->members->removeMember($project, $user, request()->user());

        return response()->json(null, 204);
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, User $user): ProjectMemberResource
    {
        $this->authorize('manageMembers', $project);

        $membership = $this->members->updateMemberRole(
            $project,
            $user,
            new UpdateProjectMemberData(ProjectMemberRole::from($request->string('member_role')->toString())),
            $request->user(),
        );

        return new ProjectMemberResource($membership->load('user'));
    }
}
