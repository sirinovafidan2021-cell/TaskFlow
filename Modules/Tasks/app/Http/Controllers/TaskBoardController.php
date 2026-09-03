<?php
namespace Modules\Tasks\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Projects\Models\Project;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Services\TaskBoardQueryService;
class TaskBoardController { use AuthorizesRequests; public function __construct(private readonly TaskBoardQueryService $board) {} public function show(Request $request, Project $project): View { $this->authorize('view',$project); return view('tasks::board',['project'=>$project,'columns'=>$this->board->forProject($project,$request->user(),$request->string('q')->toString()),'statuses'=>TaskStatus::cases(),'query'=>$request->string('q')->toString()]); } }
