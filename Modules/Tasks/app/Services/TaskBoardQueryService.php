<?php
namespace Modules\Tasks\Services;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Projects\Models\Project;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Models\Task;
class TaskBoardQueryService { /** @return array<string, Collection<int, Task>> */ public function forProject(Project $project, User $user, ?string $search = null): array { $tasks=Task::query()->where('project_id',$project->id)->with(['assignee','creator','labels'])->when(filled($search),fn($q)=>$q->where(fn($q)=>$q->where('number','like',"%{$search}%")->orWhere('title','like',"%{$search}%")))->orderBy('rank')->get(); return collect(TaskStatus::cases())->mapWithKeys(fn($status)=>[$status->value=>$tasks->where('status',$status)->values()])->all(); } }
