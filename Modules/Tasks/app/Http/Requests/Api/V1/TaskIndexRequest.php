<?php

namespace Modules\Tasks\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;
use Modules\Tasks\Enums\TaskType;

class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'type' => ['nullable', Rule::enum(TaskType::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_before' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(['created_at', 'due_at', 'priority', 'status', 'number'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
