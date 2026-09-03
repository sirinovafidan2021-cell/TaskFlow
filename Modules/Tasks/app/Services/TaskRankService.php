<?php
namespace Modules\Tasks\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Projects\Enums\ProjectStatus;
use Modules\Projects\Services\ProjectMemberService;
use Modules\Tasks\Data\ReorderTaskData;
use Modules\Tasks\Exceptions\TaskStatusConflict;
use Modules\Tasks\Models\Task;

class TaskRankService
{
    public function __construct(private readonly ProjectMemberService $members, private readonly ActivityRecorder $activity) {}
    public function placeAtEnd(Task $task): Task { $task->rank = ((int) Task::query()->where('project_id',$task->project_id)->where('status',$task->status->value)->whereKeyNot($task->id)->max('rank')) + 1000; $task->save(); return $task; }
    public function reorder(Task $task, ReorderTaskData $data, User $actor): Task
    {
        return DB::transaction(function () use ($task,$data,$actor): Task {
            $task = Task::query()->with('project')->lockForUpdate()->findOrFail($task->id);
            if ($task->project->status !== ProjectStatus::Active || ! $this->members->canManage($task->project,$actor)) throw new LogicException('Only project managers can reorder tasks.');
            if ($task->version !== $data->expectedVersion) throw new TaskStatusConflict('This task was changed by another request.');
            $items = Task::query()->where('project_id',$task->project_id)->where('status',$task->status->value)->lockForUpdate()->orderBy('rank')->orderBy('id')->get();
            $ids = $items->pluck('id')->all(); $index = array_search($task->id,$ids,true); array_splice($ids,$index,1);
            foreach ([$data->beforeTaskId,$data->afterTaskId] as $id) if ($id !== null && ! in_array($id,$ids,true)) throw new LogicException('Reorder neighbors must belong to the same project and status column.');
            if ($data->beforeTaskId !== null && $data->afterTaskId !== null && array_search($data->beforeTaskId,$ids,true) + 1 !== array_search($data->afterTaskId,$ids,true)) throw new LogicException('Reorder neighbors must be adjacent.');
            $at = $data->beforeTaskId !== null ? array_search($data->beforeTaskId,$ids,true) : ($data->afterTaskId !== null ? array_search($data->afterTaskId,$ids,true)+1 : count($ids)); array_splice($ids,$at,0,[$task->id]);
            foreach ($ids as $offset => $id) { $row = $items->firstWhere('id',$id) ?? $task; $rank = ($offset+1)*1000; if ($row->rank !== $rank) { $row->rank=$rank; if ($row->id === $task->id) $row->version++; $row->save(); } }
            $task->refresh(); $this->activity->record('task.reordered',$actor,$task,['project_id'=>$task->project_id,'task_id'=>$task->id,'rank'=>$task->rank]); return $task;
        });
    }
}
