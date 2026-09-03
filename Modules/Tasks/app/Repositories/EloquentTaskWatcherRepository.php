<?php

namespace Modules\Tasks\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;

class EloquentTaskWatcherRepository implements TaskWatcherRepository
{
    public function ensureWatching(Task $task, User $user): void
    {
        $task->watchers()->syncWithoutDetaching([$user->id]);
    }
    public function removeWatching(Task $task, User $user): void { $task->watchers()->detach($user->id); }
    public function removeForProject(Project $project, User $user): int { return \Illuminate\Support\Facades\DB::table('task_watchers')->where('user_id', $user->id)->whereIn('task_id', Task::query()->where('project_id', $project->id)->select('id'))->delete(); }
    public function removeForUser(User $user): int { return \Illuminate\Support\Facades\DB::table('task_watchers')->where('user_id', $user->id)->delete(); }
    public function eligibleWatchers(Task $task): Collection
    {
        return User::query()->where('status', \App\Enums\AccountStatus::Active->value)->whereIn('id', $task->watchers()->select('users.id'))->where(function ($query) use ($task): void { $query->whereKey($task->project->owner_id)->orWhereHas('projectMemberships', fn ($memberships) => $memberships->where('project_id', $task->project_id)); })->get();
    }
}
