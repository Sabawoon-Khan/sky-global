<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Forms\AttachmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AttachmentTypeController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        $attachmentTypes = AttachmentType::query()
            ->withCount('personnelAttachments')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('mis/forms/AttachmentTypes/Index', [
            'attachmentTypes' => $attachmentTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:attachment_types,slug'],
            'requires_expiry' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        AttachmentType::query()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Attachment type created.');
    }

    public function update(Request $request, AttachmentType $attachmentType): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:attachment_types,slug,'.$attachmentType->id],
            'requires_expiry' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $attachmentType->update($validated);

        return back()->with('success', 'Attachment type updated.');
    }
}
