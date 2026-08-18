<?php

namespace Modules\Projects\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProjectApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|array|string>>
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
