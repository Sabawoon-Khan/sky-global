<?php

namespace App\Http\Controllers\Training;

use App\Enums\TrainingPath;
use App\Enums\TrainingStatus;
use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\ServesStoredFiles;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingGuard;
use App\Models\Training\TrainingMinistryPayment;
use App\Services\DocumentNumberService;
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

class TrainingMinistryPaymentController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions, ServesStoredFiles;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters($request);

        $query = TrainingMinistryPayment::query()->withCount('guards');
        $this->applyListFilters($query, $filters, [
            'date_column' => 'payment_date',
            'search_columns' => ['reference_number', 'notes'],
        ]);

        $payments = (clone $query)
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/training/Payments/Index', [
            'payments' => $payments,
            'stats' => [
                'total' => TrainingMinistryPayment::query()->count(),
                'amount' => (float) TrainingMinistryPayment::query()->sum('total_amount'),
                'guards' => TrainingGuard::query()->where('training_path', TrainingPath::Ministry->value)->count(),
            ],
            'chart' => [
                'monthly' => $this->countCreatedByMonth(TrainingMinistryPayment::query()),
            ],
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'training.create');

        $selected = collect(explode(',', (string) $request->input('guard_ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $available = TrainingGuard::query()
            ->where('status', TrainingStatus::Registered->value)
            ->orderBy('batch_number')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'father_name',
                'grandfather_name',
                'tazkira_number',
                'id_card_number',
                'batch_number',
                'start_date',
                'end_date',
            ]);

        $batches = TrainingGuard::query()
            ->where('status', TrainingStatus::Registered->value)
            ->whereNotNull('batch_number')
            ->select('batch_number')
            ->selectRaw('COUNT(*) as guard_count')
            ->selectRaw('MIN(start_date) as training_start')
            ->selectRaw('MAX(end_date) as training_end')
            ->groupBy('batch_number')
            ->orderByRaw('MAX(id) DESC')
            ->get()
            ->map(fn ($row) => [
                'batch_number' => $row->batch_number,
                'guard_count' => (int) $row->guard_count,
                'training_start' => $row->training_start,
                'training_end' => $row->training_end,
            ])
            ->values()
            ->all();

        $preselectedBatch = $request->string('batch')->trim()->toString();
        if ($preselectedBatch === '' && $selected !== []) {
            $preselectedBatch = (string) TrainingGuard::query()
                ->whereIn('id', $selected)
                ->value('batch_number');
        }

        return Inertia::render('mis/training/Payments/Create', [
            'availableGuards' => $available,
            'selectedIds' => $selected,
            'batches' => $batches,
            'preselectedBatch' => $preselectedBatch ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'training.create');

        $validated = $request->validate([
            'batch_number' => ['required', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'max:10240'],
            'guards' => ['required', 'array', 'min:1'],
            'guards.*.id' => ['required', 'integer', Rule::exists('training_guards', 'id')],
            'guards.*.fee_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $guardIds = collect($validated['guards'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $registered = TrainingGuard::query()
            ->whereIn('id', $guardIds)
            ->where('status', TrainingStatus::Registered->value)
            ->get()
            ->keyBy('id');

        if ($registered->count() !== count($guardIds)) {
            return back()
                ->withInput()
                ->with('error', __('Select registered guards that are not yet assigned.'));
        }

        $batchNumber = $validated['batch_number'];
        $wrongBatch = $registered->first(
            fn (TrainingGuard $guard) => $guard->batch_number !== $batchNumber,
        );

        if ($wrongBatch) {
            return back()
                ->withInput()
                ->with('error', __('Guards must belong to the selected batch.'));
        }

        $total = collect($validated['guards'])->sum(fn (array $row) => (float) $row['fee_amount']);

        $payment = DB::transaction(function () use ($request, $validated, $registered, $total, $batchNumber) {
            $receiptPath = null;
            $original = null;

            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $receiptPath = $file->store('training-receipts', 'local');
                $original = $file->getClientOriginalName();
            }

            $payment = TrainingMinistryPayment::query()->create([
                'reference_number' => DocumentNumberService::nextMinistryPaymentNumber(),
                'batch_number' => $batchNumber,
                'payment_date' => $validated['payment_date'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'total_amount' => $total,
                'currency' => 'AFN',
                'receipt_path' => $receiptPath,
                'original_filename' => $original,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['guards'] as $row) {
                $guard = $registered->get((int) $row['id']);
                if (! $guard) {
                    continue;
                }

                $from = $guard->status;
                $guard->update([
                    'training_path' => TrainingPath::Ministry->value,
                    'status' => TrainingStatus::Ministry->value,
                    'ministry_payment_id' => $payment->id,
                    'ministry_period_start' => $validated['period_start'],
                    'ministry_period_end' => $validated['period_end'],
                    'fee_amount' => $row['fee_amount'],
                ]);
                $guard->logStatusChange(TrainingStatus::Ministry->value, $from, $request->user());
            }

            return $payment;
        });

        $this->notifyMisCreated(
            'training',
            $payment->reference_number,
            route('training.payments.show', $payment, false),
        );

        return redirect()
            ->route('training.payments.show', $payment)
            ->with('success', __('Payment to Public Protection Deputy recorded.'));
    }

    public function show(Request $request, TrainingMinistryPayment $trainingMinistryPayment): Response
    {
        $this->authorizePermission($request, 'training.view');

        $trainingMinistryPayment->load(['guards', 'createdBy']);

        return Inertia::render('mis/training/Payments/Show', [
            'payment' => $trainingMinistryPayment,
        ]);
    }

    public function destroy(Request $request, TrainingMinistryPayment $trainingMinistryPayment): RedirectResponse
    {
        $this->authorizePermission($request, 'training.delete');

        $hasCertified = $trainingMinistryPayment->guards()
            ->where('status', TrainingStatus::Certified->value)
            ->exists();

        abort_if($hasCertified, 422, __('This payment cannot be deleted because certificates have already been issued.'));

        DB::transaction(function () use ($request, $trainingMinistryPayment): void {
            foreach ($trainingMinistryPayment->guards as $guard) {
                $from = $guard->status;
                $guard->update([
                    'training_path' => null,
                    'status' => TrainingStatus::Registered->value,
                    'ministry_payment_id' => null,
                    'ministry_period_start' => null,
                    'ministry_period_end' => null,
                    'fee_number' => null,
                    'fee_amount' => null,
                ]);
                $guard->logStatusChange(TrainingStatus::Registered->value, $from, $request->user());
            }

            if ($trainingMinistryPayment->receipt_path) {
                Storage::disk('local')->delete($trainingMinistryPayment->receipt_path);
            }

            $trainingMinistryPayment->delete();
        });

        $this->notifyMisDeleted(
            'training',
            $trainingMinistryPayment->reference_number,
            route('training.payments.index', [], false),
        );

        return redirect()
            ->route('training.payments.index')
            ->with('success', __('Payment deleted.'));
    }

    public function downloadReceipt(Request $request, TrainingMinistryPayment $trainingMinistryPayment): StreamedResponse
    {
        $this->authorizePermission($request, 'training.view');
        abort_unless($trainingMinistryPayment->receipt_path, 404);

        return $this->serveLocalFile(
            $request,
            $trainingMinistryPayment->receipt_path,
            $trainingMinistryPayment->original_filename ?: basename($trainingMinistryPayment->receipt_path),
        );
    }

    /**
     * @param  Builder<TrainingMinistryPayment>  $query
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
