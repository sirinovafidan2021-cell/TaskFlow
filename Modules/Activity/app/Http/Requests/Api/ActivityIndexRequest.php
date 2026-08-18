<?php

namespace Modules\Activity\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ActivityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['event' => ['nullable', 'string', 'max:255'], 'project_id' => ['nullable', 'integer', 'exists:projects,id'], 'task_id' => ['nullable', 'integer', 'exists:tasks,id'], 'actor_id' => ['nullable', 'integer', 'exists:users,id'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']];
    }
}
