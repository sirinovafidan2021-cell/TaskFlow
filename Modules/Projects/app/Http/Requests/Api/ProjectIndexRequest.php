<?php

namespace Modules\Projects\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Projects\Enums\ProjectStatus;

class ProjectIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::enum(ProjectStatus::class)], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
