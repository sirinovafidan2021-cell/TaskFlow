<?php

namespace Modules\Tasks\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tasks\Enums\TaskPriority;
use Modules\Tasks\Enums\TaskStatus;

class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', Rule::enum(TaskStatus::class)], 'priority' => ['nullable', Rule::enum(TaskPriority::class)], 'project_id' => ['nullable', 'integer', 'exists:projects,id'], 'assignee_id' => ['nullable', 'integer', 'exists:users,id'], 'due_before' => ['nullable', 'date'], 'sort' => ['nullable', Rule::in(['created_at', 'due_at', 'priority', 'status', 'number'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])]];
    }
}
