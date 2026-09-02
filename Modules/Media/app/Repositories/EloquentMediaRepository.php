<?php

namespace Modules\Media\Repositories;

use Modules\Media\Models\Media;

class EloquentMediaRepository implements MediaRepository
{
    public function save(Media $media): Media
    {
        $media->save();

        return $media;
    }

    public function findByUuidOrFail(string $uuid): Media
    {
        return Media::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }
}
