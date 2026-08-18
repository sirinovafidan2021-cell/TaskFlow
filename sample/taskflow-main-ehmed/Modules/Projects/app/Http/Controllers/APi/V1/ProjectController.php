<?php

namespace Modules\Projects\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Http\Requests\Api\V1\AddProjectMemberApiRequest;
use Modules\Projects\Http\Requests\Api\V1\CreateProjectApiRequest;
use Modules\Projects\Http\Requests\Api\V1\UpdateProjectApiRequest;
use Modules\Projects\Http\Resources\ProjectMemberResource;
use Modules\Projects\Http\Resources\ProjectResource;
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

    public function index(Request $request)
    {
        $projects = $this->projects->paginateForUser(
            $request->user(),
            $request->integer('per_page', 15),
        );

        return ProjectResource::collection($projects);
    }

    public function store(CreateProjectApiRequest $request): ProjectResource
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

        return new ProjectResource($project);
    }

    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return new ProjectResource($project);
    }

    public function update(
        UpdateProjectApiRequest $request,
        Project $project,
    ): ProjectResource {
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

        $project = $this->projects->update($project, $data);

        return new ProjectResource($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projects->delete($project);

        return response()->json(status: 204);
    }

    public function members(Project $project)
    {
        Gate::authorize('view', $project);

        $members = $this->projectMembers->list($project->id);

        return ProjectMemberResource::collection($members);
    }

    public function addMember(AddProjectMemberApiRequest $request, Project $project): ProjectMemberResource
    {
        Gate::authorize('manageMembers', $project);

        $member = $this->projectMembers->addMember(
            projectId: $project->id,
            userId: $request->integer('user_id'),
            memberRole: $request->string('member_role')->toString(),
        );

        return new ProjectMemberResource($member);
    }

    public function removeMember(Project $project, ProjectMember $projectMember): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        if ($projectMember->project_id !== $project->id) {
            abort(404);
        }

        $this->projectMembers->removeMember($projectMember);

        return response()->json(status: 204);
    }
}
