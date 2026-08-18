<?php

namespace Modules\Projects\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProjectApiRequest extends FormRequest
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
        $project = $this->route('project');

        return [
            'name' => ['required', 'string'],
            'slug' => [
                'required',
                'string',
                Rule::unique('projects', 'slug')->ignore($project),
            ],
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
