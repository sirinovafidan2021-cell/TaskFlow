<?php
namespace Modules\Tasks\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Projects\Models\Project;
use Modules\Tasks\Services\BacklogQueryService;
class BacklogController { use AuthorizesRequests; public function __construct(private readonly BacklogQueryService $backlog) {} public function show(Request $request, Project $project): View { $this->authorize('view',$project); return view('tasks::backlog',['project'=>$project,'tasks'=>$this->backlog->paginate($project,$request->user())]); } }
