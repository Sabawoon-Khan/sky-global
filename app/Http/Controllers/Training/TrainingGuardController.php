<?php

namespace App\Http\Controllers\Training;

use App\Enums\TrainingPath;
use App\Enums\TrainingStatus;
use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\ServesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingGuard;
use App\Services\DocumentNumberService;
use App\Support\CompanyDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingGuardController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, ServesStoredFiles;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters($request, TrainingStatus::values(), ['batch_number', 'training_path']);

        $query = TrainingGuard::query();
        $this->applyListFilters($query, $filters, [
            'date_column' => 'start_date',
            'search_columns' => ['name', 'father_name', 'grandfather_name', 'tazkira_number', 'id_card_number', 'batch_number'],
        ]);
        $query
            ->when($filters['batch_number'] ?? null, fn ($q, string $batch) => $q->where('batch_number', $batch))
            ->when($filters['training_path'] ?? null, fn ($q, string $path) => $q->where('training_path', $path));

        $guards = (clone $query)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $byStatus = TrainingGuard::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        $registered = (int) ($byStatus[TrainingStatus::Registered->value] ?? 0);
        $ministry = (int) ($byStatus[TrainingStatus::Ministry->value] ?? 0);
        $company = (int) ($byStatus[TrainingStatus::Company->value] ?? 0);
        $certified = (int) ($byStatus[TrainingStatus::Certified->value] ?? 0);

        return Inertia::render('mis/training/Guards/Index', [
            'guards' => $guards,
            'stats' => [
                'total' => TrainingGuard::query()->count(),
                'registered' => $registered,
                'ministry' => $ministry,
                'company' => $company,
                'certified' => $certified,
                'by_status' => $byStatus,
            ],
            'chart' => [
                'status' => [
                    ['key' => 'registered', 'label' => 'registered', 'value' => $registered],
                    ['key' => 'ministry', 'label' => 'ministry', 'value' => $ministry],
                    ['key' => 'company', 'label' => 'company', 'value' => $company],
                    ['key' => 'completed', 'label' => 'completed', 'value' => (int) ($byStatus[TrainingStatus::Completed->value] ?? 0)],
                    ['key' => 'certified', 'label' => 'certified', 'value' => $certified],
                ],
                'monthly' => $this->countCreatedByMonth(TrainingGuard::query()),
            ],
            'batches' => TrainingGuard::query()
                ->whereNotNull('batch_number')
                ->distinct()
                ->orderBy('batch_number')
                ->pluck('batch_number')
                ->values()
                ->all(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'training.create');

        return Inertia::render('mis/training/Guards/Create', [
            'batches' => $this->recentBatches(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'training.create');

        $validated = $this->validatedGuard($request);

        $guard = TrainingGuard::query()->create([
            ...$validated,
            'status' => TrainingStatus::Registered->value,
            'created_by' => $request->user()?->id,
        ]);
        $guard->logStatusChange($guard->status, null, $request->user());

        $this->notifyMisCreated(
            'training',
            $guard->name,
            route('training.guards.show', $guard, false),
        );

        return redirect()
            ->route('training.guards.show', $guard)
            ->with('success', __('Guard registered for training.'));
    }

    public function show(Request $request, TrainingGuard $trainingGuard): Response
    {
        $this->authorizePermission($request, 'training.view');

        $trainingGuard->load([
            'ministryPayment',
            'createdBy',
            'statusChangeLogs.changedBy',
            'employee:id,name',
            'contractor:id,name',
        ]);

        return Inertia::render('mis/training/Guards/Show', [
            'guard' => $trainingGuard,
            'next_certificate_number' => $trainingGuard->certificate_number
                ?: DocumentNumberService::nextTrainingCertificateNumber(),
        ]);
    }

    public function edit(Request $request, TrainingGuard $trainingGuard): Response
    {
        $this->authorizePermission($request, 'training.edit');

        return Inertia::render('mis/training/Guards/Edit', [
            'guard' => $trainingGuard,
            'batches' => $this->recentBatches(),
        ]);
    }

    public function update(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.edit');

        $trainingGuard->update($this->validatedGuard($request));

        $this->notifyMisUpdated(
            'training',
            $trainingGuard->name,
            route('training.guards.show', $trainingGuard, false),
        );

        return redirect()
            ->route('training.guards.show', $trainingGuard)
            ->with('success', __('Guard updated.'));
    }

    public function destroy(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.delete');

        $name = $trainingGuard->name;
        $trainingGuard->delete();

        $this->notifyMisDeleted('training', $name, route('training.guards.index', [], false));

        return redirect()
            ->route('training.guards.index')
            ->with('success', __('Guard removed.'));
    }

    public function company(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters($request, [TrainingStatus::Company->value, TrainingStatus::Completed->value, TrainingStatus::Certified->value], ['batch_number']);

        $query = TrainingGuard::query()->where('training_path', TrainingPath::Company->value);
        $this->applyListFilters($query, $filters, [
            'date_column' => 'start_date',
            'search_columns' => ['name', 'father_name', 'tazkira_number', 'id_card_number', 'batch_number'],
        ]);
        $query->when($filters['batch_number'] ?? null, fn ($q, string $batch) => $q->where('batch_number', $batch));

        $available = TrainingGuard::query()
            ->where('status', TrainingStatus::Registered->value)
            ->orderBy('name')
            ->get(['id', 'name', 'father_name', 'batch_number', 'tazkira_number', 'id_card_number']);

        return Inertia::render('mis/training/Company/Index', [
            'guards' => (clone $query)->latest()->paginate(15)->withQueryString(),
            'availableGuards' => $available,
            'stats' => [
                'total' => TrainingGuard::query()->where('training_path', TrainingPath::Company->value)->count(),
                'in_training' => TrainingGuard::query()
                    ->where('training_path', TrainingPath::Company->value)
                    ->where('status', TrainingStatus::Company->value)
                    ->count(),
                'certified' => TrainingGuard::query()
                    ->where('training_path', TrainingPath::Company->value)
                    ->where('status', TrainingStatus::Certified->value)
                    ->count(),
            ],
            'filters' => $filters,
        ]);
    }

    public function assignCompany(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.edit');

        if (! $trainingGuard->canAssignCompany()) {
            return back()->with('error', __('This guard is already assigned to training.'));
        }

        $validated = $request->validate([
            'company_trainer' => ['nullable', 'string', 'max:255'],
            'company_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $from = $trainingGuard->status;
        $trainingGuard->update([
            ...$validated,
            'training_path' => TrainingPath::Company->value,
            'status' => TrainingStatus::Company->value,
        ]);
        $trainingGuard->logStatusChange(TrainingStatus::Company->value, $from, $request->user());

        $this->notifyMisStatus(
            'training',
            $trainingGuard->name,
            TrainingStatus::Company->value,
            route('training.guards.show', $trainingGuard, false),
        );

        return back()->with('success', __('Guard assigned to company training.'));
    }

    public function assignCompanyBulk(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'training.edit');

        $validated = $request->validate([
            'guard_ids' => ['required', 'array', 'min:1'],
            'guard_ids.*' => ['integer', Rule::exists('training_guards', 'id')],
            'company_trainer' => ['nullable', 'string', 'max:255'],
            'company_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $guards = TrainingGuard::query()
            ->whereIn('id', $validated['guard_ids'])
            ->where('status', TrainingStatus::Registered->value)
            ->get();

        if ($guards->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', __('Select registered guards that are not yet assigned.'));
        }

        DB::transaction(function () use ($guards, $validated, $request): void {
            foreach ($guards as $guard) {
                $from = $guard->status;
                $guard->update([
                    'training_path' => TrainingPath::Company->value,
                    'status' => TrainingStatus::Company->value,
                    'company_trainer' => $validated['company_trainer'] ?? null,
                    'company_location' => $validated['company_location'] ?? null,
                    'notes' => $validated['notes'] ?? $guard->notes,
                ]);
                $guard->logStatusChange(TrainingStatus::Company->value, $from, $request->user());
            }
        });

        $this->notifyMisCustom(
            'training',
            __('Guards assigned to company training'),
            __(':count guards assigned to company training.', ['count' => $guards->count()]),
            route('training.company.index', [], false),
        );

        return redirect()
            ->route('training.company.index')
            ->with('success', __('Guards assigned to company training.'));
    }

    public function complete(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.edit');

        if (! $trainingGuard->canComplete()) {
            return back()->with('error', __('This guard is not currently in training.'));
        }

        $from = $trainingGuard->status;
        $trainingGuard->update(['status' => TrainingStatus::Completed->value]);
        $trainingGuard->logStatusChange(TrainingStatus::Completed->value, $from, $request->user());

        $this->notifyMisStatus(
            'training',
            $trainingGuard->name,
            TrainingStatus::Completed->value,
            route('training.guards.show', $trainingGuard, false),
        );

        return back()->with('success', __('Training marked as completed.'));
    }

    public function issueCertificate(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'training.edit');

        if (! $trainingGuard->canIssueCertificate()) {
            return back()->with('error', __('This guard is not ready for a certificate.'));
        }

        $validated = $request->validate([
            'certificate_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('training_guards', 'certificate_number')->ignore($trainingGuard),
            ],
            'certificate_issued_at' => ['nullable', 'date'],
            'certificate' => ['nullable', 'file', 'max:10240'],
        ]);

        $from = $trainingGuard->status;
        $path = $trainingGuard->certificate_path;
        $original = $trainingGuard->certificate_original_filename;

        if ($request->hasFile('certificate')) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            $file = $request->file('certificate');
            $path = $file->store('training-certificates', 'local');
            $original = $file->getClientOriginalName();
        }

        $number = DocumentNumberService::resolve(
            $validated['certificate_number'] ?? $trainingGuard->certificate_number,
            fn () => DB::transaction(fn () => DocumentNumberService::nextTrainingCertificateNumber()),
        );

        $trainingGuard->update([
            'status' => TrainingStatus::Certified->value,
            'certificate_number' => $number,
            'certificate_issued_at' => $validated['certificate_issued_at'] ?? now()->toDateString(),
            'certificate_path' => $path,
            'certificate_original_filename' => $original,
        ]);
        $trainingGuard->logStatusChange(TrainingStatus::Certified->value, $from, $request->user());

        $this->notifyMisStatus(
            'training',
            $trainingGuard->name,
            TrainingStatus::Certified->value,
            route('training.guards.show', $trainingGuard, false),
        );

        return back()->with('success', __('Certificate of completion issued.'));
    }

    public function printCertificate(Request $request, TrainingGuard $trainingGuard): Response
    {
        $this->authorizePermission($request, 'training.view');
        abort_unless($trainingGuard->isCertified(), 404);

        return Inertia::render('mis/training/Guards/CertificatePrint', [
            'guard' => $trainingGuard,
            'company' => CompanyDocument::profile(),
        ]);
    }

    public function downloadCertificate(Request $request, TrainingGuard $trainingGuard): StreamedResponse
    {
        $this->authorizePermission($request, 'training.view');
        abort_unless($trainingGuard->certificate_path, 404);

        return $this->serveLocalFile(
            $request,
            $trainingGuard->certificate_path,
            $trainingGuard->certificate_original_filename ?: basename($trainingGuard->certificate_path),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedGuard(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'grandfather_name' => ['nullable', 'string', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'batch_number' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /** @return list<string> */
    protected function recentBatches(): array
    {
        return TrainingGuard::query()
            ->whereNotNull('batch_number')
            ->select('batch_number')
            ->groupBy('batch_number')
            ->orderByRaw('MAX(id) DESC')
            ->limit(20)
            ->pluck('batch_number')
            ->values()
            ->all();
    }

    /**
     * @param  Builder<TrainingGuard>  $query
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
}
