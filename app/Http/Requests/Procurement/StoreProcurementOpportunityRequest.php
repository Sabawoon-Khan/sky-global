<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bidding.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'submission_deadline' => ['nullable', 'date'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'security_scope' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:open,closed,cancelled,awarded'],
        ];
    }
}
