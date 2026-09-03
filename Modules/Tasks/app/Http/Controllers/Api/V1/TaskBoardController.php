<?php
namespace Modules\Tasks\Http\Controllers\Api\V1;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Projects\Models\Project;
use Modules\Tasks\Http\Resources\TaskResource;
use Modules\Tasks\Services\TaskBoardQueryService;
class TaskBoardController { use AuthorizesRequests; public function __construct(private readonly TaskBoardQueryService $board) {} public function show(Request $request, Project $project) { $this->authorize('view',$project); return response()->json(['data'=>collect($this->board->forProject($project,$request->user(),$request->string('q')->toString()))->map(fn($tasks)=>TaskResource::collection($tasks)->resolve())->all()]); } }
