<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTemplateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('project-templates.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'weight' => ['nullable', 'numeric', 'gt:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'start_offset_days' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $task = $this->route('template_task');
            if ($task !== null && $task->children()->exists() && $this->input('weight') !== null) {
                $validator->errors()->add('weight', 'Parent task tidak boleh menyimpan weight.');
            }

            if ($task !== null && ! $task->children()->exists() && $this->input('weight') === null) {
                $validator->errors()->add('weight', 'Leaf task wajib memiliki weight lebih besar dari 0.');
            }
        }];
    }
}
