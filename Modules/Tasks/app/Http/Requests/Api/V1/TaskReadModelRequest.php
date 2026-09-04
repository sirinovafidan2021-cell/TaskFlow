<?php

namespace Modules\Tasks\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TaskReadModelRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:180'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
