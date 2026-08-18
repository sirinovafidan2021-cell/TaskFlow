<?php

namespace Modules\Tasks\Repositories\Contracts;

use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

interface TaskAttachmentRepositoryInterface
{
    /** @return Collection<int, TaskAttachment> */
    public function forTask(Task $task): Collection;

    public function save(TaskAttachment $attachment): TaskAttachment;

    public function delete(TaskAttachment $attachment): void;
}
