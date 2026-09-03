<?php
namespace Modules\Tasks\Services;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
class BacklogQueryService { public function paginate(Project $project, User $user, int $perPage = 25): LengthAwarePaginator { return Task::query()->where('project_id',$project->id)->with(['assignee','labels'])->orderBy('status')->orderBy('rank')->paginate($perPage)->withQueryString(); } }
