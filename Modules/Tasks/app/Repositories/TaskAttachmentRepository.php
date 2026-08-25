<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

interface TaskAttachmentRepository
{
    public function forTask(Task $task): Collection;

    public function paginateForTask(Task $task, int $perPage): LengthAwarePaginator;

    public function save(TaskAttachment $attachment): TaskAttachment;

    public function delete(TaskAttachment $attachment): void;
}
