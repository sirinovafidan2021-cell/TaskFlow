<?php

namespace Modules\Projects\Data;

final readonly class ProjectData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public string $status,
        public ?string $startsAt,
        public ?string $dueAt,
        public int $ownerId,
    ) {}
}
