<?php
namespace Modules\Tasks\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Str;
use Modules\Tasks\Enums\TaskLabelColor;
use Modules\Tasks\Models\TaskLabel;

class StoreTaskLabelRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['name' => trim((string) $this->input('name')), 'color' => strtoupper((string) $this->input('color'))]); }
    public function rules(): array { return ['name' => ['required', 'string', 'max:80', 'regex:/.*\\S.*/u'], 'color' => ['required', Rule::enum(TaskLabelColor::class)]]; }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('name')) return;
            $project = $this->route('project'); $name = $this->string('name')->toString(); $slug = Str::slug($name);
            if ($slug === '') { $validator->errors()->add('name', 'The label name must contain letters or numbers.'); return; }
            $query = TaskLabel::query()->where('project_id', $project->id)->where(fn ($query) => $query->where('name', $name)->orWhere('slug', $slug));
            if ($label = $this->route('label')) $query->whereKeyNot($label->id);
            if ($query->exists()) $validator->errors()->add('name', 'The label name is already in use for this project.');
        });
    }
}
