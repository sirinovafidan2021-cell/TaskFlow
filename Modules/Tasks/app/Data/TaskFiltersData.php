<?php

namespace Modules\Tasks\Data;

final readonly class TaskFiltersData
{
    public function __construct(public ?string $q, public ?string $status, public ?string $priority, public ?int $projectId, public ?int $assigneeId, public ?string $dueBefore, public string $sort, public string $direction) {}

    public static function fromArray(array $data): self
    {
        return new self($data['q'] ?? $data['search'] ?? null, $data['status'] ?? null, $data['priority'] ?? null, isset($data['project_id']) ? (int) $data['project_id'] : null, isset($data['assignee_id']) ? (int) $data['assignee_id'] : null, $data['due_before'] ?? null, $data['sort'] ?? 'created_at', $data['direction'] ?? 'desc');
    }
}
