<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Forms\PersonnelAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelAttachmentController extends Controller
{
    use AuthorizesMisPermissions;

    public function download(Request $request, PersonnelAttachment $personnelAttachment): StreamedResponse
    {
        $this->authorizePermission($request, 'hr.view');

        abort_unless(Storage::disk('local')->exists($personnelAttachment->file_path), 404);

        return Storage::disk('local')->download(
            $personnelAttachment->file_path,
            basename($personnelAttachment->file_path),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'personnel_id' => ['required', 'integer'],
            'attachment_type_id' => ['required', 'exists:attachment_types,id'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('personnel-attachments', 'local');

        PersonnelAttachment::query()->create([
            ...collect($validated)->except('file')->all(),
            'file_path' => $path,
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Attachment uploaded.');
    }

    public function destroy(Request $request, PersonnelAttachment $personnelAttachment): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.delete');

        if ($personnelAttachment->file_path) {
            Storage::disk('local')->delete($personnelAttachment->file_path);
        }

        $personnelAttachment->delete();

        return back()->with('success', 'Attachment removed.');
    }
}
