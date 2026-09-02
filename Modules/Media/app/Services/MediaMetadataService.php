<?php

namespace Modules\Media\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Media\Data\MediaMetadataData;
use Modules\Media\Models\Media;
use Modules\Media\Repositories\MediaRepository;

class MediaMetadataService
{
    public function __construct(private readonly MediaRepository $media) {}

    /** Persists metadata produced by the trusted storage pipeline. */
    public function register(User $uploader, MediaMetadataData $data): Media
    {
        return DB::transaction(function () use ($uploader, $data): Media {
            return $this->media->save(new Media([
                'uuid' => $data->uuid,
                'uploaded_by' => $uploader->id,
                'disk' => $data->disk,
                'path' => $data->path,
                'original_name' => $data->originalName,
                'extension' => $data->extension,
                'mime_type' => $data->mimeType,
                'size' => $data->size,
                'sha256' => $data->sha256,
                'image_width' => $data->imageWidth,
                'image_height' => $data->imageHeight,
            ]));
        });
    }

    /**
     * @param array<int, MediaMetadataData> $items
     * @return array<int, Media>
     */
    public function registerMany(User $uploader, array $items): array
    {
        return DB::transaction(fn (): array => array_map(
            fn (MediaMetadataData $item): Media => $this->register($uploader, $item),
            $items,
        ));
    }

    public function delete(Media $media): void
    {
        DB::transaction(fn (): mixed => $this->media->delete($media));
    }
}
