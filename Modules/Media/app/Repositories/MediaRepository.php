<?php

namespace Modules\Media\Repositories;

use Modules\Media\Models\Media;

interface MediaRepository
{
    public function save(Media $media): Media;

    public function findByUuidOrFail(string $uuid): Media;

    public function delete(Media $media): void;
}
