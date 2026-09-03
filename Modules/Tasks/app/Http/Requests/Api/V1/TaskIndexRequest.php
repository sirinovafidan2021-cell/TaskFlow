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
            'statuses' => ['nullable','array'], 'statuses.*' => [Rule::enum(TaskStatus::class)],
            'types' => ['nullable','array'], 'types.*' => [Rule::enum(TaskType::class)],
            'priorities' => ['nullable','array'], 'priorities.*' => [Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reporter_id' => ['nullable','integer','exists:users,id'], 'parent_id' => ['nullable','integer','exists:tasks,id'],
            'label_ids' => ['nullable','array'], 'label_ids.*' => ['integer','exists:task_labels,id'],
            'due_before' => ['nullable', 'date'],
            'due_after' => ['nullable','date'], 'overdue' => ['nullable','boolean'],
            'sort' => ['nullable', Rule::in(['number','-number','created_at','-created_at','updated_at','-updated_at','due_at','-due_at','priority','-priority','status','-status','rank','-rank'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
