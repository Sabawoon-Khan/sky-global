<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Forms\PersonnelAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait StoresPersonnelAttachments
{
    /**
     * @return array<string, mixed>
     */
    protected function personnelAttachmentValidationRules(): array
    {
        return [
            'personnel_forms' => ['nullable', 'array'],
            'personnel_forms.*.attachment_type_id' => ['required', 'exists:attachment_types,id'],
            'personnel_forms.*.file' => ['required', 'file', 'max:10240'],
            'personnel_forms.*.issued_at' => ['nullable', 'date'],
            'personnel_forms.*.expires_at' => ['nullable', 'date'],
            'personnel_forms.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function storePersonnelAttachments(Request $request, Model $model, string $personnelType): void
    {
        $forms = $request->input('personnel_forms', []);

        if (! is_array($forms)) {
            return;
        }

        foreach ($forms as $index => $form) {
            $file = $request->file("personnel_forms.{$index}.file");

            if (! $file) {
                continue;
            }

            $path = $file->store('personnel-attachments', 'local');

            PersonnelAttachment::query()->create([
                'personnel_type' => $personnelType,
                'personnel_id' => $model->getKey(),
                'attachment_type_id' => $form['attachment_type_id'],
                'file_path' => $path,
                'issued_at' => $form['issued_at'] ?? null,
                'expires_at' => $form['expires_at'] ?? null,
                'notes' => $form['notes'] ?? null,
                'verified_by' => $request->user()?->id,
            ]);
        }
    }
}
