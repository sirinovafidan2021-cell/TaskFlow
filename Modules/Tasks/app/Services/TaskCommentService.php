<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;
use Modules\Tasks\Repositories\Contracts\TaskCommentRepositoryInterface;

class TaskCommentService
{
    public function __construct(private readonly TaskCommentRepositoryInterface $comments, private readonly ActivityRecorder $activity) {}

    public function create(Task $task, User $actor, string $body): TaskComment
    {
        return DB::transaction(function () use ($task, $actor, $body) {
            $comment = $this->comments->save(new TaskComment(['task_id' => $task->id, 'user_id' => $actor->id, 'body' => $body]));
            $this->activity->record('comment.created', $actor, $comment, ['project_id' => $task->project_id, 'task_id' => $task->id, 'comment_id' => $comment->id]);

            return $comment;
        });
    }

    public function delete(TaskComment $comment, User $actor): void
    {
        DB::transaction(function () use ($comment, $actor) {
            $taskId = $comment->task_id;
            $projectId = $comment->task->project_id;
            $commentId = $comment->id;
            $this->comments->delete($comment);
            $this->activity->record('comment.deleted', $actor, $comment, ['project_id' => $projectId, 'task_id' => $taskId, 'comment_id' => $commentId]);
        });
    }
}
