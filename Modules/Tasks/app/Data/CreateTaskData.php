<?php

namespace Modules\Tasks\Data;

use Modules\Tasks\Enums\TaskPriority;

final readonly class CreateTaskData
{
    public function __construct(public int $projectId, public string $title, public ?string $description, public ?int $assigneeId, public TaskPriority $priority, public ?string $dueAt) {}

    public static function fromArray(int $projectId, array $data): self
    {
        return new self($projectId, $data['title'], $data['description'] ?? null, $data['assignee_id'] ?? null, TaskPriority::from($data['priority']), $data['due_at'] ?? null);
    }
}
