<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait StoresOptionalAttachments
{
    protected function storeOptionalAttachment(Request $request, Model $model, string $field = 'attachment'): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $request->validate([
            $field => ['file', 'max:10240'],
            'attachment_title' => ['nullable', 'string', 'max:255'],
            'attachment_notes' => ['nullable', 'string'],
        ]);

        $file = $request->file($field);
        $path = $file->store('attachments/'.class_basename($model), 'local');

        $model->attachments()->create([
            'title' => $request->input('attachment_title') ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'notes' => $request->input('attachment_notes'),
            'uploaded_by' => $request->user()?->id,
        ]);
    }
}
