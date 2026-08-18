<?php
namespace Modules\Tasks\Repositories;
use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;
class EloquentTaskCommentRepository implements TaskCommentRepository { public function forTask(Task $task): Collection { return TaskComment::query()->with('user')->where('task_id',$task->id)->oldest()->get(); } public function save(TaskComment $comment): TaskComment { $comment->save(); return $comment; } public function delete(TaskComment $comment): void { $comment->delete(); } }
