<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tasks\Rules\AllowedTaskAttachmentFile;

class UploadTaskAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file', 'max:10240', new AllowedTaskAttachmentFile],
        ];
    }
}
