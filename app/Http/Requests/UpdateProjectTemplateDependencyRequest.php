<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplateTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTemplateDependencyRequest extends FormRequest
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
            'predecessor_template_task_id' => [
                'required',
                'integer',
                Rule::notIn([$this->route('template_task')->id]),
                Rule::exists((new ProjectTemplateTask)->getTable(), 'id')->where(
                    fn ($query) => $query->where('project_template_id', $this->route('project_template')->id)
                ),
            ],
            'dependency_type' => ['required', Rule::in(['FS', 'SS', 'FF', 'SF'])],
            'lag_days' => ['required', 'integer', 'min:0'],
        ];
    }
}
