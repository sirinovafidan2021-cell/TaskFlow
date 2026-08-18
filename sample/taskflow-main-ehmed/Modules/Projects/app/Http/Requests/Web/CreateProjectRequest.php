<?php

namespace Modules\Projects\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<\Illuminate\Contracts\Validation\ValidationRule|array|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'slug' => ['required', 'string', Rule::unique('projects', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    'draft',
                    'active',
                    'completed',
                    'archived',
                ]),
            ],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
