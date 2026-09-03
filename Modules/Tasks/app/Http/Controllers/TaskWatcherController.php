<?php
namespace Modules\Tasks\Http\Controllers;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Modules\Tasks\Http\Requests\ManageTaskWatcherRequest;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskWatcherService;
class TaskWatcherController { use AuthorizesRequests; public function __construct(private readonly TaskWatcherService $watchers) {} public function store(ManageTaskWatcherRequest $request, Task $task): RedirectResponse { $user = $request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : $request->user(); $this->authorize('watch', [$task, $user]); $this->watchers->watch($task, $user, $request->user()); return back()->with('success', 'Watcher added.'); } public function destroy(ManageTaskWatcherRequest $request, Task $task, User $user): RedirectResponse { $this->authorize('watch', [$task, $user]); $this->watchers->unwatch($task, $user, $request->user()); return back()->with('success', 'Watcher removed.'); } }
