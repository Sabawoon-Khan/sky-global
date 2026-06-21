<?php

namespace App\Http\Requests\Archive;

use App\Enums\DocumentDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchivedDocumentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'direction' => ['required', 'string', Rule::enum(DocumentDirection::class)],
            'document_category_id' => ['nullable', 'exists:document_categories,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'bid_id' => ['nullable', 'exists:bids,id'],
            'file' => ['required', 'file', 'max:20480'],
            'document_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }
}
