<?php

namespace Modules\Tasks\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AllowedTaskAttachmentFile implements ValidationRule
{
    /**
     * @var array<string, array<int, string>>
     */
    private const MIME_TYPES_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'text' => ['text/plain'],
        'log' => ['text/plain'],
        'md' => ['text/plain'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mimeType = strtolower($value->getMimeType() ?: 'application/octet-stream');

        if (! isset(self::MIME_TYPES_BY_EXTENSION[$extension])) {
            $fail('The :attribute must be a supported PDF, image, text, Word, or Excel file.');

            return;
        }

        if (! in_array($mimeType, self::MIME_TYPES_BY_EXTENSION[$extension], true)) {
            $fail('The :attribute content does not match the uploaded file type.');
        }
    }
}
