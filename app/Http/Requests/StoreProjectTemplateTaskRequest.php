<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplateTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTemplateTaskRequest extends FormRequest
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
            'parent_id' => [
                'nullable',
                Rule::exists((new ProjectTemplateTask)->getTable(), 'id')->where(
                    fn ($query) => $query->where('project_template_id', $this->route('project_template')->id)
                ),
            ],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'weight' => ['required', 'numeric', 'gt:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'start_offset_days' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
        ];
    }
}
