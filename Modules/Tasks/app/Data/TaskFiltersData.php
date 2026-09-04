<?php

namespace Modules\Tasks\Data;

final readonly class TaskFiltersData
{
    public function __construct(public ?string $q, public array $statuses, public array $priorities, public ?int $projectId, public ?int $assigneeId, public ?int $reporterId, public array $labelIds, public ?int $parentId, public ?string $dueBefore, public ?string $dueAfter, public bool $overdue, public string $sort, public array $types = [], public bool $unassigned = false) {}

    public static function fromArray(array $data): self
    {
        return new self($data['q'] ?? $data['search'] ?? null, array_values($data['statuses'] ?? (isset($data['status']) ? [$data['status']] : [])), array_values($data['priorities'] ?? (isset($data['priority']) ? [$data['priority']] : [])), isset($data['project_id']) ? (int) $data['project_id'] : null, array_key_exists('assignee_id',$data) && $data['assignee_id'] !== 'unassigned' ? (int)$data['assignee_id'] : null, isset($data['reporter_id']) ? (int)$data['reporter_id'] : null, array_map('intval',$data['label_ids'] ?? (isset($data['label_id']) ? [$data['label_id']] : [])), isset($data['parent_id']) ? (int)$data['parent_id'] : null, $data['due_before'] ?? null, $data['due_after'] ?? null, (bool)($data['overdue'] ?? false), $data['sort'] ?? '-created_at', array_values($data['types'] ?? (isset($data['type']) ? [$data['type']] : [])), ($data['assignee_id'] ?? null) === 'unassigned');
    }
}
