<?php
namespace Modules\Tasks\Http\Controllers\Api\V1;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Tasks\Http\Requests\ManageTaskWatcherRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskWatcherService;
class TaskWatcherController { use AuthorizesRequests; public function __construct(private readonly TaskWatcherService $watchers) {} public function index(Task $task): JsonResponse { $this->authorize('view', $task); return response()->json(['data' => $task->watchers()->orderBy('name')->get(['users.id', 'users.name'])]); } public function store(ManageTaskWatcherRequest $request, Task $task): JsonResponse { $user = $request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : $request->user(); $this->authorize('watch', [$task, $user]); $this->watchers->watch($task, $user, $request->user()); return response()->json(null, 204); } public function destroy(ManageTaskWatcherRequest $request, Task $task, User $user): JsonResponse { $this->authorize('watch', [$task, $user]); $this->watchers->unwatch($task, $user, $request->user()); return response()->json(null, 204); } }
