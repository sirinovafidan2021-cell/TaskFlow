<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tasks\Enums\TaskPriority;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'min:3', 'max:180'], 'description' => ['nullable', 'string', 'max:10000'], 'priority' => ['required', Rule::enum(TaskPriority::class)], 'due_at' => ['nullable', 'date']];
    }
}
