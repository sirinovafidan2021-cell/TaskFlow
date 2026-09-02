<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tasks\Models\TaskAttachment;

/** @mixin TaskAttachment */
class TaskAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_uuid' => $this->media?->uuid,
            'original_name' => $this->media?->original_name,
            'mime_type' => $this->media?->mime_type,
            'size' => $this->media?->size,
            'uploaded_by' => $this->whenLoaded('uploader', fn (): array => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'preview_url' => route('api.v1.tasks.media.preview', [$this->task_id, $this->id]),
            'download_url' => route('api.v1.tasks.media.download', [$this->task_id, $this->id]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
