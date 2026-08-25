<?php

namespace Modules\Projects\Data;

final readonly class ProjectData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $startsAt,
        public ?string $dueAt,
    ) {}

    /**
     * @param  array{name: string, description?: string|null, starts_at?: string|null, due_at?: string|null}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            name: $attributes['name'],
            description: $attributes['description'] ?? null,
            startsAt: $attributes['starts_at'] ?? null,
            dueAt: $attributes['due_at'] ?? null,
        );
    }
}
