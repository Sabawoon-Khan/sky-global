<?php

namespace App\Http\Controllers\Archive;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\GeneratesMisReferenceNumbers;
use App\Http\Controllers\Controller;
use App\Models\Archive\ArchivedDocument;
use App\Models\Archive\DocumentCategory;
use App\Models\Organization;
use App\Models\Project\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArchivedDocumentController extends Controller
{
    use AuthorizesMisPermissions, GeneratesMisReferenceNumbers;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'archive.view');

        $search = $request->string('search')->trim()->toString();
        $direction = $request->string('direction')->trim()->toString();

        $documents = ArchivedDocument::query()
            ->with(['documentCategory', 'organization', 'project'])
            ->where('is_archived', false)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            }))
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->latest('document_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/archive/Index', [
            'documents' => $documents,
            'filters' => [
                'search' => $search ?: null,
                'direction' => $direction ?: null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.create');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'direction' => ['required', 'string', 'in:incoming,outgoing,internal'],
            'document_category_id' => ['nullable', 'exists:document_categories,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'bid_id' => ['nullable', 'exists:bids,id'],
            'document_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store('archive', 'local');

        ArchivedDocument::query()->create([
            ...collect($validated)->except('file')->all(),
            'reference_number' => $this->generateArchiveReferenceNumber(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Document archived.');
    }

    public function show(Request $request, ArchivedDocument $archivedDocument): Response
    {
        $this->authorizePermission($request, 'archive.view');

        $archivedDocument->load([
            'documentCategory',
            'organization',
            'project',
            'bid',
            'uploadedBy',
            'links',
        ]);

        return Inertia::render('mis/archive/Show', [
            'document' => $archivedDocument,
            'categories' => DocumentCategory::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, ArchivedDocument $archivedDocument): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.edit');

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'direction' => ['sometimes', 'string', 'in:incoming,outgoing,internal'],
            'document_category_id' => ['nullable', 'exists:document_categories,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'bid_id' => ['nullable', 'exists:bids,id'],
            'document_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        if ($request->hasFile('file')) {
            if ($archivedDocument->file_path) {
                Storage::disk('local')->delete($archivedDocument->file_path);
            }

            $file = $request->file('file');
            $validated['file_path'] = $file->store('archive', 'local');
            $validated['original_filename'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
            $validated['version'] = ($archivedDocument->version ?? 1) + 1;
        }

        unset($validated['file']);
        $archivedDocument->update($validated);

        return back()->with('success', 'Document updated.');
    }

    public function archive(Request $request, ArchivedDocument $archivedDocument): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.archive');

        $archivedDocument->update(['is_archived' => true]);

        return redirect()
            ->route('archive.index')
            ->with('success', 'Document moved to long-term archive.');
    }
}
