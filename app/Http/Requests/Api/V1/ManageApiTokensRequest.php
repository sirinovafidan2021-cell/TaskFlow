<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;

class ManageApiTokensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo(PermissionName::ApiTokensManage->value) ?? false;
    }
}
