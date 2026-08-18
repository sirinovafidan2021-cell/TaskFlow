<?php

namespace Modules\Tasks\Data;

use Modules\Tasks\Enums\TaskPriority;

final readonly class UpdateTaskData
{
    public function __construct(public string $title, public ?string $description, public TaskPriority $priority, public ?string $dueAt) {}

    public static function fromArray(array $data): self
    {
        return new self($data['title'], $data['description'] ?? null, TaskPriority::from($data['priority']), $data['due_at'] ?? null);
    }
}
