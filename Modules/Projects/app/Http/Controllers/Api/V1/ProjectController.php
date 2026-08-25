<?php

namespace Modules\Projects\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Http\Requests\Api\V1\ProjectIndexRequest;
use Modules\Projects\Http\Requests\StoreProjectRequest;
use Modules\Projects\Http\Requests\UpdateProjectRequest;
use Modules\Projects\Http\Resources\ProjectResource;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\ProjectRepository;
use Modules\Projects\Services\ProjectService;

class ProjectController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectService $projectService,
    ) {}

    public function index(ProjectIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        return ProjectResource::collection($this->projects->paginateFor(
            $request->user(),
            $request->string('search')->trim()->toString(),
            $request->string('status')->toString(),
            $request->integer('per_page', 12),
        ));
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load('owner'));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create(
            $request->user(),
            ProjectData::fromArray($request->validated()),
        );

        return (new ProjectResource($project->load('owner')))->response()->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', [$project, true]);

        $project = $this->projectService->update(
            $project,
            ProjectData::fromArray($request->validated()),
            $request->user(),
        );

        return new ProjectResource($project->load('owner'));
    }

    public function activate(Request $request, Project $project): ProjectResource
    {
        $this->authorize('update', [$project, true]);

        return new ProjectResource($this->projectService->activate($project, $request->user())->load('owner'));
    }

    public function archive(Request $request, Project $project): ProjectResource
    {
        $this->authorize('archive', [$project, true]);

        return new ProjectResource($this->projectService->archive($project, $request->user())->load('owner'));
    }
}
