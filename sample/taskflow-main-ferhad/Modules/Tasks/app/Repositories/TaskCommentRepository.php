<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskComment;

interface TaskCommentRepository
{
    public function forTask(Task $task): Collection;

    public function save(TaskComment $comment): TaskComment;

    public function delete(TaskComment $comment): void;
}
