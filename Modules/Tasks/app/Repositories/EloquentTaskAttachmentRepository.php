<?php

namespace Modules\Tasks\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

class EloquentTaskAttachmentRepository implements TaskAttachmentRepository
{
    public function forTask(Task $task): Collection
    {
        return TaskAttachment::query()->with(['uploader', 'media'])->where('task_id', $task->id)->latest()->get();
    }

    public function paginateForTask(Task $task, int $perPage): LengthAwarePaginator
    {
        return TaskAttachment::query()->with(['uploader', 'media'])->where('task_id', $task->id)->latest()->paginate($perPage)->withQueryString();
    }

    public function save(TaskAttachment $attachment): TaskAttachment
    {
        $attachment->save();

        return $attachment;
    }

    public function delete(TaskAttachment $attachment): void
    {
        $attachment->delete();
    }
}
