<?php
namespace Modules\Tasks\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UploadTaskAttachmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['attachment'=>['required','file','max:10240','mimetypes:application/pdf,image/png,image/jpeg,image/webp,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']]; } }
