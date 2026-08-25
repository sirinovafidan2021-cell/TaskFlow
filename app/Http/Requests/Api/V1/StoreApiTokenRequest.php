<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ApiTokenAbility;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends ManageApiTokensRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'distinct:strict', Rule::enum(ApiTokenAbility::class)],
        ];
    }
}
