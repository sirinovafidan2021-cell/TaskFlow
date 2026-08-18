<?php

namespace Modules\Tasks\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;
use Modules\Tasks\Repositories\Contracts\TaskCommentRepositoryInterface;

class EloquentTaskCommentRepository implements TaskCommentRepositoryInterface
{
    public function forTask(Task $task): Collection
    {
        return TaskComment::query()->with('user')->where('task_id', $task->id)->oldest()->get();
    }

    public function save(TaskComment $comment): TaskComment
    {
        $comment->save();

        return $comment;
    }

    public function delete(TaskComment $comment): void
    {
        $comment->delete();
    }
}
