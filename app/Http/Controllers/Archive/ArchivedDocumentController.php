<?php

namespace App\Http\Controllers\Archive;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\GeneratesMisReferenceNumbers;
use App\Http\Controllers\Concerns\ServesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\Archive\ArchivedDocument;
use App\Models\Archive\DocumentCategory;
use App\Models\Organization;
use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivedDocumentController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, GeneratesMisReferenceNumbers, ServesStoredFiles;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'archive.view');

        $filters = $this->listFilters($request, [], ['direction', 'document_category_id']);

        $query = ArchivedDocument::query()
            ->with(['documentCategory', 'organization', 'project'])
            ->where('is_archived', false);
        $this->applyListFilters($query, $filters, [
            'date_column' => 'document_date',
            'search_columns' => ['title', 'reference_number'],
        ]);
        $query
            ->when($filters['direction'] ?? null, fn ($q, string $direction) => $q->where('direction', $direction))
            ->when($filters['document_category_id'] ?? null, fn ($q, int $categoryId) => $q->where('document_category_id', $categoryId));

        $documents = (clone $query)
            ->latest('document_date')
            ->paginate(15)
            ->withQueryString();

        $base = ArchivedDocument::query()->where('is_archived', false);
        $incoming = (clone $base)->where('direction', 'incoming')->count();
        $outgoing = (clone $base)->where('direction', 'outgoing')->count();
        $internal = (clone $base)->where('direction', 'internal')->count();
        $archived = ArchivedDocument::query()->where('is_archived', true)->count();

        return Inertia::render('mis/archive/Index', [
            'documents' => $documents,
            'stats' => [
                'total' => (clone $base)->count(),
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'internal' => $internal,
                'archived' => $archived,
            ],
            'chart' => [
                'status' => [
                    ['key' => 'incoming', 'label' => 'Incoming', 'value' => $incoming],
                    ['key' => 'outgoing', 'label' => 'Outgoing', 'value' => $outgoing],
                    ['key' => 'internal', 'label' => 'Internal', 'value' => $internal],
                ],
                'monthly' => $this->countCreatedByMonth(
                    ArchivedDocument::query()->where('is_archived', false)
                ),
            ],
            'documentCategories' => DocumentCategory::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * @param  Builder<ArchivedDocument>  $query
     * @return list<array{key: string, label: string, value: int}>
     */
    protected function countCreatedByMonth($query): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = $query
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get(['created_at']);

        foreach ($rows as $row) {
            if (! $row->created_at) {
                continue;
            }
            $key = $row->created_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'archive.create');

        return Inertia::render('mis/archive/Create', [
            'categories' => DocumentCategory::query()->orderBy('name')->get(['id', 'name']),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'code', 'name']),
            'next_reference_number' => $this->generateArchiveReferenceNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.create');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:50', 'unique:archived_documents,reference_number'],
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

        $document = ArchivedDocument::query()->create([
            ...collect($validated)->except('file')->all(),
            'reference_number' => filled($validated['reference_number'] ?? null)
                ? $validated['reference_number']
                : $this->generateArchiveReferenceNumber(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        $this->notifyMisCreated(
            'archive',
            $document->title,
            route('archive.show', $document, false),
        );

        return redirect()
            ->route('archive.index')
            ->with('success', 'Document archived.');
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

    public function download(Request $request, ArchivedDocument $archivedDocument): StreamedResponse
    {
        $this->authorizePermission($request, 'archive.view');

        abort_unless((bool) $archivedDocument->file_path, 404);

        return $this->serveLocalFile(
            $request,
            $archivedDocument->file_path,
            $archivedDocument->original_filename ?? basename($archivedDocument->file_path),
        );
    }

    public function update(Request $request, ArchivedDocument $archivedDocument): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.edit');

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'reference_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('archived_documents', 'reference_number')->ignore($archivedDocument),
            ],
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

        $this->notifyMisUpdated(
            'archive',
            $archivedDocument->title,
            route('archive.show', $archivedDocument, false),
        );

        return back()->with('success', 'Document updated.');
    }

    public function archive(Request $request, ArchivedDocument $archivedDocument): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.archive');

        $archivedDocument->update(['is_archived' => true]);

        $this->notifyMisStatus(
            'archive',
            $archivedDocument->title,
            'archived',
            route('archive.index', [], false),
        );

        return redirect()
            ->route('archive.index')
            ->with('success', 'Document moved to long-term archive.');
    }

    public function destroy(Request $request, ArchivedDocument $archivedDocument): RedirectResponse
    {
        $this->authorizePermission($request, 'archive.delete');

        $title = $archivedDocument->title;
        $archivedDocument->delete();

        $this->notifyMisDeleted('archive', $title, route('archive.index', [], false));

        return redirect()
            ->route('archive.index')
            ->with('success', 'Document deleted.');
    }
}
