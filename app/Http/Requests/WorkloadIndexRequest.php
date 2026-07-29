<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorkloadIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'string'],
            'period' => ['nullable', Rule::in(['this_week', 'next_7_days', 'this_month', 'custom'])],
            'start_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'workspace' => ['nullable', 'integer', 'exists:workspaces,id'],
            'project' => ['nullable', 'integer', 'exists:projects,id'],
            'level' => ['nullable', Rule::in(['normal', 'attention', 'high_risk', 'critical'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('period') !== 'custom'
                    || ! $this->filled(['start_date', 'end_date'])
                    || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $startDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('start_date'))->startOfDay();
                $endDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('end_date'))->startOfDay();

                if ($startDate->diffInDays($endDate) > (int) config('atur.workload.custom_range_max_days', 366)) {
                    $validator->errors()->add(
                        'end_date',
                        'Rentang tanggal kustom tidak boleh melebihi 366 hari.',
                    );
                }
            },
        ];
    }
}
