<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\ServesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingFieldGuard;
use App\Models\Training\TrainingFieldReport;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingFieldReportController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, ServesStoredFiles;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters($request);

        $query = TrainingFieldReport::query()->withCount('guards');
        $this->applyListFilters($query, $filters, [
            'date_column' => 'report_date',
            'search_columns' => ['reference_number', 'description', 'trainer_name'],
        ]);

        $reports = (clone $query)
            ->latest('report_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/training/Field/Reports/Index', [
            'reports' => $reports,
            'stats' => [
                'roster' => TrainingFieldGuard::query()->count(),
                'reports' => TrainingFieldReport::query()->count(),
            ],
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'training.create');

        $preselected = collect(explode(',', (string) $request->input('guard_ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $roster = TrainingFieldGuard::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'father_name',
                'tazkira_number',
                'id_card_number',
                'site',
            ]);

        return Inertia::render('mis/training/Field/Reports/Create', [
            'roster' => $roster,
            'selectedIds' => $preselected,
            'next_reference_number' => DocumentNumberService::nextFieldTrainingReportNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'training.create');

        $validated = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:50', Rule::unique('training_field_reports', 'reference_number')],
            'report_date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'guard_ids' => ['required', 'array', 'min:1'],
            'guard_ids.*' => [
                'integer',
                Rule::exists('training_field_guards', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $report = DB::transaction(function () use ($request, $validated) {
            $path = null;
            $original = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('training-field-reports', 'local');
                $original = $file->getClientOriginalName();
            }

            $report = TrainingFieldReport::query()->create([
                'reference_number' => DocumentNumberService::resolve(
                    $validated['reference_number'] ?? null,
                    fn () => DocumentNumberService::nextFieldTrainingReportNumber(),
                ),
                'report_date' => $validated['report_date'],
                'description' => $validated['description'],
                'trainer_name' => $validated['trainer_name'] ?? null,
                'attachment_path' => $path,
                'original_filename' => $original,
                'created_by' => $request->user()?->id,
            ]);

            $report->guards()->sync($validated['guard_ids']);

            return $report;
        });

        $this->notifyMisCreated(
            'training',
            $report->reference_number,
            route('training.field.reports.show', $report, false),
        );

        return redirect()
            ->route('training.field.reports.show', $report)
            ->with('success', __('Training visit report saved.'));
    }

    public function show(Request $request, TrainingFieldReport $trainingFieldReport): Response
    {
        $this->authorizePermission($request, 'training.view');

        $trainingFieldReport->load(['guards', 'createdBy']);

        return Inertia::render('mis/training/Field/Reports/Show', [
            'report' => $trainingFieldReport,
        ]);
    }

    public function destroy(Request $request, TrainingFieldReport $trainingFieldReport): RedirectResponse
    {
        $this->authorizePermission($request, 'training.delete');

        if ($trainingFieldReport->attachment_path) {
            Storage::disk('local')->delete($trainingFieldReport->attachment_path);
        }

        $reference = $trainingFieldReport->reference_number;
        $trainingFieldReport->delete();

        $this->notifyMisDeleted(
            'training',
            $reference,
            route('training.field.reports.index', [], false),
        );

        return redirect()
            ->route('training.field.reports.index')
            ->with('success', __('Training visit report deleted.'));
    }

    public function downloadAttachment(Request $request, TrainingFieldReport $trainingFieldReport): StreamedResponse
    {
        $this->authorizePermission($request, 'training.view');
        abort_unless($trainingFieldReport->attachment_path, 404);

        return $this->serveLocalFile(
            $request,
            $trainingFieldReport->attachment_path,
            $trainingFieldReport->original_filename ?: basename($trainingFieldReport->attachment_path),
        );
    }
}
