<?php

namespace Modules\Projects\Data;

use Modules\Projects\Enums\ProjectStatus;

final readonly class ProjectFiltersData
{
    public function __construct(
        public ?string $search = null,
        public ?ProjectStatus $status = null,
        public int $perPage = 12,
    ) {}

    /**
     * @param  array{q?: string|null, status?: string|null, per_page?: int|null}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            search: filled($attributes['q'] ?? null) ? trim((string) $attributes['q']) : null,
            status: ProjectStatus::tryFrom((string) ($attributes['status'] ?? '')),
            perPage: min(max((int) ($attributes['per_page'] ?? 12), 1), 100),
        );
    }
}
