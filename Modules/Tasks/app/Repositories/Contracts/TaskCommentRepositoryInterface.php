<?php

namespace Modules\Tasks\Repositories\Contracts;

use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;

interface TaskCommentRepositoryInterface
{
    /** @return Collection<int, TaskComment> */
    public function forTask(Task $task): Collection;

    public function save(TaskComment $comment): TaskComment;

    public function delete(TaskComment $comment): void;
}
