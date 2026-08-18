<?php

namespace Modules\Projects\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

final class AddProjectMemberRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'member_role' => ['required', 'string'],
        ];
    }
}
