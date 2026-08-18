<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Repositories\TaskAttachmentRepository;

class TaskAttachmentService
{
    public function __construct(private readonly TaskAttachmentRepository $attachments, private readonly ActivityRecorder $activity) {}

    public function upload(Task $task, User $actor, UploadedFile $file): TaskAttachment
    {
        $disk = 'local';
        $path = $file->store("task-attachments/{$task->id}", $disk);
        try {
            return DB::transaction(function () use ($task, $actor, $file, $disk, $path) {
                $attachment = $this->attachments->save(new TaskAttachment(['task_id' => $task->id, 'uploaded_by' => $actor->id, 'disk' => $disk, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType() ?? 'application/octet-stream', 'size' => $file->getSize()]));
                $this->activity->record('attachment.uploaded', $actor, $attachment, ['project_id' => $task->project_id, 'task_id' => $task->id, 'attachment_id' => $attachment->id, 'filename' => $attachment->original_name]);

                return $attachment;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function download(TaskAttachment $attachment)
    {
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function delete(TaskAttachment $attachment, User $actor): void
    {
        DB::transaction(function () use ($attachment, $actor) {
            $properties = ['project_id' => $attachment->task->project_id, 'task_id' => $attachment->task_id, 'attachment_id' => $attachment->id, 'filename' => $attachment->original_name];
            Storage::disk($attachment->disk)->delete($attachment->path);
            $this->attachments->delete($attachment);
            $this->activity->record('attachment.deleted', $actor, $attachment, $properties);
        });
    }
}
