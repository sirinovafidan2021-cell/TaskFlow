<?php

namespace Modules\Tasks\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Services\ActivityRecorder;
use Modules\Activity\Enums\ActivityEvent;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaStorageService;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Repositories\TaskAttachmentRepository;
use Modules\Projects\Enums\ProjectStatus;

class TaskAttachmentService
{
    public function __construct(
        private readonly TaskAttachmentRepository $attachments,
        private readonly ActivityRecorder $activity,
        private readonly MediaStorageService $media,
    ) {}

    public function upload(Task $task, User $actor, UploadedFile $file): TaskAttachment
    {
        return $this->uploadMany($task, $actor, [$file])[0];
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, TaskAttachment>
     */
    public function uploadMany(Task $task, User $actor, array $files): array
    {
        if ($task->project->status !== ProjectStatus::Active) { throw new \LogicException('Only active projects accept attachments.'); }
        $mediaItems = $this->media->storeMany($actor, $files);

        try {
            return DB::transaction(function () use ($task, $actor, $mediaItems): array {
                return array_map(function (Media $media) use ($task, $actor): TaskAttachment {
                    // Legacy fields are retained for migration verification only; Media is authoritative.
                    $attachment = $this->attachments->save(new TaskAttachment([
                        'task_id' => $task->id,
                        'media_id' => $media->id,
                        'uploaded_by' => $actor->id,
                        'disk' => $media->disk,
                        'path' => $media->path,
                        'original_name' => $media->original_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                    ]));
                    $this->activity->record(ActivityEvent::AttachmentUploaded, $actor, $attachment, [
                        'project_id' => $task->project_id,
                        'task_id' => $task->id,
                        'attachment_id' => $attachment->id,
                        'media_uuid' => $media->uuid,
                        'filename' => $media->original_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                    ]);
                    $attachment->setRelation('media', $media);

                    return $attachment;
                }, $mediaItems);
            });
        } catch (\Throwable $e) {
            foreach ($mediaItems as $media) {
                try {
                    $this->media->delete($media);
                } catch (\Throwable) {
                    // The association failure is the caller-visible error; cleanup can be retried safely.
                }
            }
            throw $e;
        }
    }

    public function download(TaskAttachment $attachment)
    {
        return $this->media->download($this->mediaFor($attachment));
    }

    public function preview(TaskAttachment $attachment)
    {
        return $this->media->preview($this->mediaFor($attachment));
    }

    public function delete(TaskAttachment $attachment, User $actor): void
    {
        if ($attachment->task->project->status !== ProjectStatus::Active) { throw new \LogicException('Only active projects allow attachment changes.'); }
        $media = $this->mediaFor($attachment);
        DB::transaction(function () use ($attachment, $actor, $media) {
            $properties = ['project_id' => $attachment->task->project_id, 'task_id' => $attachment->task_id, 'attachment_id' => $attachment->id, 'media_uuid' => $media->uuid, 'filename' => $media->original_name];
            $this->attachments->delete($attachment);
            $this->activity->record(ActivityEvent::AttachmentDeleted, $actor, $attachment, $properties);
        });
        $this->media->delete($media);
    }

    private function mediaFor(TaskAttachment $attachment): Media
    {
        $media = $attachment->relationLoaded('media') ? $attachment->media : $attachment->load('media')->media;

        if (! $media instanceof Media) {
            throw new \LogicException('The attachment has no media record.');
        }

        return $media;
    }
}
