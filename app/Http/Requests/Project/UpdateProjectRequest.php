<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'scope_summary' => ['nullable', 'string'],
            'total_contract_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['sometimes', 'string', 'in:planning,active,suspended,completed,closed'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'detail' => ['nullable', 'array'],
            'detail.client_requirements' => ['nullable', 'string'],
            'detail.risk_notes' => ['nullable', 'string'],
            'detail.special_instructions' => ['nullable', 'string'],
            'detail.guards_required' => ['nullable', 'integer', 'min:0'],
            'detail.supervisors_required' => ['nullable', 'integer', 'min:0'],
            'detail.shift_details' => ['nullable', 'string'],
            'detail.equipment_requirements' => ['nullable', 'string'],
            'detail.training_requirements' => ['nullable', 'string'],
            'detail.client_contact_on_site' => ['nullable', 'string', 'max:255'],
            'detail.reporting_frequency' => ['nullable', 'string', 'max:100'],
            'detail.internal_notes' => ['nullable', 'string'],
        ];
    }
}
