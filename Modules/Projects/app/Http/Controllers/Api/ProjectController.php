<?php

namespace Modules\Projects\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Projects\Data\ProjectData;
use Modules\Projects\Data\ProjectFiltersData;
use Modules\Projects\Http\Requests\Api\ProjectIndexRequest;
use Modules\Projects\Http\Requests\Api\StoreProjectRequest;
use Modules\Projects\Http\Requests\Api\UpdateProjectRequest;
use Modules\Projects\Http\Resources\ProjectResource;
use Modules\Projects\Models\Project;
use Modules\Projects\Repositories\Contracts\ProjectRepositoryInterface;
use Modules\Projects\Services\ProjectService;

class ProjectController
{
    use AuthorizesRequests;

    public function __construct(private readonly ProjectRepositoryInterface $projects, private readonly ProjectService $service) {}

    public function index(ProjectIndexRequest $request)
    {
        $this->authorize('viewAny', Project::class);

        return ProjectResource::collection($this->projects->paginateVisibleTo($request->user(), ProjectFiltersData::fromArray($request->validated())))->additional(['success' => true, 'message' => 'Projects retrieved successfully.']);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json(['success' => true, 'message' => 'Project retrieved successfully.', 'data' => new ProjectResource($project->load('owner')->loadCount('memberships'))]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);
        $project = $this->service->create($request->user(), ProjectData::fromArray($request->validated()));

        return response()->json(['success' => true, 'message' => 'Project created successfully.', 'data' => new ProjectResource($project->load('owner'))], 201);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        $project = $this->service->update($project, ProjectData::fromArray($request->validated()), $request->user());

        return response()->json(['success' => true, 'message' => 'Project updated successfully.', 'data' => new ProjectResource($project->load('owner'))]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $this->service->delete($project, request()->user());

        return response()->json(['success' => true, 'message' => 'Project deleted successfully.']);
    }
}
