<?php

namespace Modules\Activity\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ActivityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string', 'max:120'],
            'project' => ['nullable', 'integer', 'exists:projects,id'],
            'task' => ['nullable', 'integer', 'exists:tasks,id'],
            'actor' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
