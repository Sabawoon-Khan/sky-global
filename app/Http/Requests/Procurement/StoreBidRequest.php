<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreBidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'procurement_opportunity_id' => ['required', 'exists:procurement_opportunities,id'],
            'status' => ['nullable', 'string', 'in:draft,submitted,under_review,won,lost,cancelled'],
            'submitted_at' => ['nullable', 'date'],
            'our_total_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.total' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
