<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceSheet;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollItem;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Hr\PersonnelPayrollAdjustment;
use App\Models\Project\Project;
use App\Enums\ProjectStatus;
use App\Services\PayrollCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function __construct(
        private readonly PayrollCalculationService $calculator,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $year = $request->filled('year') ? $request->integer('year') : null;
        $month = $request->filled('month') ? $request->integer('month') : null;
        $projectId = $request->integer('project_id') ?: null;

        $payrollRuns = PayrollRun::query()
            ->with(['project', 'creator', 'processedBy'])
            ->withCount('items')
            ->withSum('items as total_net', 'net_amount')
            ->when($year !== null, fn ($query) => $query->where('period_year', $year))
            ->when($month !== null, fn ($query) => $query->where('period_month', $month))
            ->when($projectId !== null, fn ($query) => $query->where('project_id', $projectId))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PayrollRun $run) => [
                'id' => $run->id,
                'title' => $run->title,
                'payroll_type' => $run->payroll_type,
                'project' => $run->project?->only(['id', 'code', 'name']),
                'date_from' => $run->date_from?->toDateString(),
                'date_to' => $run->date_to?->toDateString(),
                'period_year' => $run->period_year,
                'period_month' => $run->period_month,
                'status' => $run->status,
                'items_count' => $run->items_count,
                'total_net' => $run->total_net,
                'processed_by' => $run->processedBy?->only(['name']),
                'created_by_name' => $run->creator?->name,
            ]);

        $period = $this->defaultPeriod($request);

        return Inertia::render('mis/hr/Payroll/Index', [
            'payrollRuns' => $payrollRuns,
            'projects' => $this->activeProjects(),
            'filters' => [
                'date_from' => $period['date_from']->toDateString(),
                'date_to' => $period['date_to']->toDateString(),
                'year' => $year,
                'month' => $month,
                'project_id' => $projectId,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        if (blank($request->input('project_id'))) {
            $request->merge(['project_id' => null]);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'payroll_type' => ['required', 'string', 'in:general,project'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id', 'required_if:payroll_type,project'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'attendance_sheet_id' => ['nullable', 'integer', 'exists:attendance_sheets,id'],
        ]);

        $payrollType = $validated['payroll_type'];
        $projectId = $payrollType === 'project' ? ($validated['project_id'] ?? null) : null;
        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = Carbon::parse($validated['date_to']);

        $sheet = $this->resolveAttendanceSheet(
            $validated['attendance_sheet_id'] ?? null,
            $dateFrom,
            $dateTo,
            $projectId,
            $payrollType,
        );

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = $this->defaultTitle($payrollType, $projectId, $dateFrom, $dateTo);
        }

        $itemCount = 0;

        $payrollRun = DB::transaction(function () use (
            $request,
            $payrollType,
            $projectId,
            $dateFrom,
            $dateTo,
            $sheet,
            $title,
            &$itemCount,
        ) {
            $payrollRun = PayrollRun::query()->create([
                'title' => $title,
                'payroll_type' => $payrollType,
                'project_id' => $projectId,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'attendance_sheet_id' => $sheet?->id,
                'period_year' => $dateFrom->year,
                'period_month' => $dateFrom->month,
                'status' => 'processed',
                'processed_by' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            $this->storeOptionalAttachment($request, $payrollRun);

            $itemCount = $this->generateItems($payrollRun, $request);

            return $payrollRun;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $itemCount > 0
                ? "Payroll generated with {$itemCount} line items."
                : 'Payroll created, but no matching attendance records were found.',
        ]);

        return redirect()->route('hr.payroll.show', $payrollRun);
    }

    public function show(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $payrollRun->load([
            'items.project',
            'items.personnel',
            'items.attendance',
            'processedBy',
            'creator',
            'project',
            'attendanceSheet',
            'attachments',
        ]);

        return Inertia::render('mis/hr/Payroll/Show', [
            'payrollRun' => $this->formatPayrollRunForView($payrollRun),
        ]);
    }

    public function print(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $payrollRun->load([
            'items.project',
            'items.personnel',
            'items.attendance',
            'project',
            'processedBy',
        ]);

        $attendanceMap = $this->attendanceMapForRun($payrollRun);

        $mapRow = fn (PayrollItem $item, int $index) => $this->mapPrintRow($item, $index, $attendanceMap);

        $sortItems = fn ($items) => $items
            ->sortBy(fn (PayrollItem $item) => strtolower(
                trim(($item->personnel?->first_name ?? '').' '.($item->personnel?->last_name ?? '')),
            ))
            ->values();

        $employeeItems = $sortItems(
            $payrollRun->items->where('personnel_type', Employee::class),
        );
        $contractorItems = $sortItems(
            $payrollRun->items->where('personnel_type', Contractor::class),
        );

        $employees = $employeeItems->map(fn (PayrollItem $item, int $index) => $mapRow($item, $index));
        $contractors = $contractorItems->map(fn (PayrollItem $item, int $index) => $mapRow($item, $index));

        $sumTotals = fn ($rows) => [
            'base' => $rows->sum('base_amount'),
            'bonus' => $rows->sum('bonus'),
            'deductions' => $rows->sum('deductions'),
            'advance' => $rows->sum('advance'),
            'net' => $rows->sum('net_amount'),
        ];

        return Inertia::render('mis/hr/Payroll/Print', [
            'payrollRun' => [
                'id' => $payrollRun->id,
                'title' => $payrollRun->title,
                'payroll_type' => $payrollRun->payroll_type,
                'date_from' => $payrollRun->date_from?->toDateString(),
                'date_to' => $payrollRun->date_to?->toDateString(),
                'status' => $payrollRun->status,
                'processed_by' => $payrollRun->processedBy?->name,
            ],
            'project' => $payrollRun->project?->only(['id', 'code', 'name', 'location']),
            'employees' => $employees,
            'contractors' => $contractors,
            'employee_totals' => $sumTotals($employees),
            'contractor_totals' => $sumTotals($contractors),
            'totals' => $sumTotals($employees->concat($contractors)),
            'period_label' => $this->periodLabel($payrollRun->date_from, $payrollRun->date_to),
        ]);
    }

    public function process(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $itemCount = DB::transaction(function () use ($payrollRun, $request) {
            $itemIds = $payrollRun->items()->pluck('id');

            if ($itemIds->isNotEmpty()) {
                PersonnelPayrollAdjustment::query()
                    ->whereIn('payroll_item_id', $itemIds)
                    ->update([
                        'payroll_item_id' => null,
                        'applied_at' => null,
                    ]);
            }

            $payrollRun->items()->delete();

            $count = $this->generateItems($payrollRun, $request);

            $payrollRun->update([
                'status' => 'processed',
                'processed_by' => $request->user()->id,
            ]);

            return $count;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $itemCount > 0
                ? "Payroll regenerated with {$itemCount} line items."
                : 'No matching attendance records were found.',
        ]);

        return back();
    }

    public function updateItem(Request $request, PayrollRun $payrollRun, PayrollItem $payrollItem): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        abort_unless($payrollItem->payroll_run_id === $payrollRun->id, 404);

        $validated = $request->validate([
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bonus = (float) ($validated['bonus'] ?? 0);
        $deductions = (float) ($validated['deductions'] ?? 0);
        $advance = (float) ($validated['advance'] ?? 0);

        $payrollItem->update([
            'bonus' => $bonus,
            'deductions' => $deductions,
            'advance' => $advance,
            'notes' => $validated['notes'] ?? $payrollItem->notes,
            'net_amount' => PayrollItem::calculateNetAmount(
                (float) $payrollItem->base_amount,
                $bonus,
                $deductions,
                $advance,
            ),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payroll line updated.',
        ]);

        return back();
    }

    public function destroy(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.delete');

        DB::transaction(function () use ($payrollRun) {
            $itemIds = $payrollRun->items()->pluck('id');

            if ($itemIds->isNotEmpty()) {
                PersonnelPayrollAdjustment::query()
                    ->whereIn('payroll_item_id', $itemIds)
                    ->update([
                        'payroll_item_id' => null,
                        'applied_at' => null,
                    ]);
            }

            $payrollRun->delete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payroll run deleted.',
        ]);

        return redirect()->route('hr.payroll.index');
    }

    private function generateItems(PayrollRun $payrollRun, Request $request): int
    {
        $attendances = $this->attendancesForRun($payrollRun);
        $itemCount = 0;

        foreach ($attendances as $attendance) {
            $calculation = $this->calculator->calculateFromAttendance($attendance);
            $baseAmount = max($calculation['base_amount'], 0.0);

            $adjustments = PersonnelPayrollAdjustment::pendingTotalsForLine(
                $attendance->personnel_type,
                $attendance->personnel_id,
                $attendance->project_id,
                $payrollRun->period_year,
                $payrollRun->period_month,
            );

            if ($adjustments['salary'] > 0) {
                $baseAmount = $adjustments['salary'];
            }

            $deductions = $calculation['absence_deduction'] + $adjustments['deductions'];

            $item = PayrollItem::query()->create([
                'payroll_run_id' => $payrollRun->id,
                'personnel_type' => $attendance->personnel_type,
                'personnel_id' => $attendance->personnel_id,
                'project_id' => $attendance->project_id,
                'personnel_attendance_id' => $attendance->id,
                'base_amount' => $baseAmount,
                'bonus' => $adjustments['bonus'],
                'deductions' => $deductions,
                'advance' => $adjustments['advance'],
                'net_amount' => PayrollItem::calculateNetAmount(
                    $baseAmount,
                    $adjustments['bonus'],
                    $deductions,
                    $adjustments['advance'],
                ),
                'currency' => $calculation['currency'],
                'notes' => null,
            ]);

            PersonnelPayrollAdjustment::markAppliedForPayrollItem(
                $item,
                $payrollRun->period_year,
                $payrollRun->period_month,
            );

            $itemCount++;
        }

        $processedKeys = $payrollRun->items()
            ->get(['personnel_type', 'personnel_id', 'project_id'])
            ->map(fn ($item) => "{$item->personnel_type}:{$item->personnel_id}:".($item->project_id ?? 'null'))
            ->all();

        $salaryOnlyAdjustments = PersonnelPayrollAdjustment::query()
            ->pending()
            ->where('period_year', $payrollRun->period_year)
            ->where('period_month', $payrollRun->period_month)
            ->where('type', PersonnelPayrollAdjustment::TYPE_SALARY)
            ->when(
                $payrollRun->payroll_type === 'project',
                fn ($query) => $query->where('project_id', $payrollRun->project_id),
                fn ($query) => $query->whereNull('project_id'),
            )
            ->get()
            ->groupBy(fn ($row) => "{$row->personnel_type}:{$row->personnel_id}:".($row->project_id ?? 'null'));

        foreach ($salaryOnlyAdjustments as $key => $rows) {
            if (in_array($key, $processedKeys, true)) {
                continue;
            }

            $first = $rows->first();
            $adjustments = PersonnelPayrollAdjustment::pendingTotalsForLine(
                $first->personnel_type,
                $first->personnel_id,
                $first->project_id,
                $payrollRun->period_year,
                $payrollRun->period_month,
            );

            $item = PayrollItem::query()->create([
                'payroll_run_id' => $payrollRun->id,
                'personnel_type' => $first->personnel_type,
                'personnel_id' => $first->personnel_id,
                'project_id' => $first->project_id,
                'base_amount' => $adjustments['salary'],
                'bonus' => $adjustments['bonus'],
                'deductions' => $adjustments['deductions'],
                'advance' => $adjustments['advance'],
                'net_amount' => PayrollItem::calculateNetAmount(
                    $adjustments['salary'],
                    $adjustments['bonus'],
                    $adjustments['deductions'],
                    $adjustments['advance'],
                ),
                'currency' => 'AFN',
                'notes' => 'Generated from salary adjustment',
            ]);

            PersonnelPayrollAdjustment::markAppliedForPayrollItem(
                $item,
                $payrollRun->period_year,
                $payrollRun->period_month,
            );

            $itemCount++;
        }

        return $itemCount;
    }

    /**
     * @return Collection<int, PersonnelAttendance>
     */
    private function attendancesForRun(PayrollRun $payrollRun): Collection
    {
        if ($payrollRun->attendance_sheet_id !== null) {
            return PersonnelAttendance::query()
                ->where('attendance_sheet_id', $payrollRun->attendance_sheet_id)
                ->get();
        }

        $query = PersonnelAttendance::query()
            ->where('year', $payrollRun->period_year)
            ->where('month', $payrollRun->period_month);

        if ($payrollRun->payroll_type === 'project') {
            $query->where('project_id', $payrollRun->project_id);
        } else {
            $query->whereNull('project_id');
        }

        return $query->get();
    }

    private function resolveAttendanceSheet(
        ?int $sheetId,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?int $projectId,
        string $payrollType,
    ): ?AttendanceSheet {
        if ($sheetId !== null) {
            return AttendanceSheet::query()->find($sheetId);
        }

        $query = AttendanceSheet::query()
            ->whereDate('date_from', $dateFrom->toDateString())
            ->whereDate('date_to', $dateTo->toDateString())
            ->where('attendance_type', $payrollType);

        if ($payrollType === 'project') {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }

        return $query->latest('id')->first();
    }

    private function defaultTitle(
        string $payrollType,
        ?int $projectId,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
    ): string {
        $period = $this->periodLabel($dateFrom, $dateTo);

        if ($payrollType === 'project' && $projectId !== null) {
            $project = Project::query()->find($projectId, ['code']);

            return "{$project?->code} Payroll — {$period}";
        }

        return "General Payroll — {$period}";
    }

    private function periodLabel(?CarbonInterface $dateFrom, ?CarbonInterface $dateTo = null): string
    {
        if ($dateFrom === null) {
            return '—';
        }

        $dateTo ??= $dateFrom;

        if (
            $dateFrom->format('Y-m') === $dateTo->format('Y-m')
            && $dateFrom->isStartOfMonth()
            && $dateTo->isEndOfMonth()
        ) {
            return $dateFrom->format('F Y');
        }

        if ($dateFrom->isSameDay($dateTo)) {
            return $dateFrom->format('F j, Y');
        }

        return $dateFrom->format('M j, Y').' – '.$dateTo->format('M j, Y');
    }

    /**
     * @return Collection<string, PersonnelAttendance>
     */
    private function attendanceMapForRun(PayrollRun $payrollRun): Collection
    {
        return $this->attendancesForRun($payrollRun)->keyBy(
            fn (PersonnelAttendance $attendance) => $this->personnelLineKey(
                $attendance->personnel_type,
                $attendance->personnel_id,
                $attendance->project_id,
            ),
        );
    }

    private function personnelLineKey(string $personnelType, int $personnelId, ?int $projectId): string
    {
        return "{$personnelType}:{$personnelId}:".($projectId ?? 'null');
    }

    private function resolveAttendanceForItem(PayrollItem $item, Collection $attendanceMap): ?PersonnelAttendance
    {
        if ($item->relationLoaded('attendance') && $item->attendance !== null) {
            return $item->attendance;
        }

        return $attendanceMap->get(
            $this->personnelLineKey($item->personnel_type, $item->personnel_id, $item->project_id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttendanceSummary(?PersonnelAttendance $attendance): ?array
    {
        if ($attendance === null) {
            return null;
        }

        return [
            'days_present' => (int) $attendance->days_present,
            'days_absent' => (int) $attendance->days_absent,
            'days_sick_leave' => (int) $attendance->days_sick_leave,
            'days_annual_leave' => (int) $attendance->days_annual_leave,
            'days_casual_leave' => (int) $attendance->days_casual_leave,
            'days_other' => (int) $attendance->days_other,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayrollRunForView(PayrollRun $payrollRun): array
    {
        $attendanceMap = $this->attendanceMapForRun($payrollRun);

        return [
            ...$payrollRun->toArray(),
            'items' => $payrollRun->items->map(function (PayrollItem $item) use ($attendanceMap) {
                $attendance = $this->resolveAttendanceForItem($item, $attendanceMap);

                return [
                    ...$item->toArray(),
                    'personnel' => $item->personnel,
                    'project' => $item->project,
                    'attendance' => $this->formatAttendanceSummary($attendance),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  Collection<string, PersonnelAttendance>  $attendanceMap
     * @return array<string, mixed>
     */
    private function mapPrintRow(PayrollItem $item, int $index, Collection $attendanceMap): array
    {
        $attendance = $this->resolveAttendanceForItem($item, $attendanceMap);
        $attendanceSummary = $this->formatAttendanceSummary($attendance);

        return [
            'no' => $index + 1,
            'name' => trim(($item->personnel?->first_name ?? '').' '.($item->personnel?->last_name ?? '')) ?: "#{$item->personnel_id}",
            'designation' => '—',
            'days_present' => $attendanceSummary['days_present'] ?? 0,
            'days_absent' => $attendanceSummary['days_absent'] ?? 0,
            'days_sick_leave' => $attendanceSummary['days_sick_leave'] ?? 0,
            'days_annual_leave' => $attendanceSummary['days_annual_leave'] ?? 0,
            'days_casual_leave' => $attendanceSummary['days_casual_leave'] ?? 0,
            'days_other' => $attendanceSummary['days_other'] ?? 0,
            'base_amount' => (float) $item->base_amount,
            'bonus' => (float) $item->bonus,
            'deductions' => (float) $item->deductions,
            'advance' => (float) $item->advance,
            'net_amount' => (float) $item->net_amount,
            'currency' => $item->currency ?? 'AFN',
        ];
    }

    /**
     * @return array{date_from: Carbon, date_to: Carbon}
     */
    private function defaultPeriod(Request $request): array
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            return [
                'date_from' => Carbon::parse($request->string('date_from')->toString()),
                'date_to' => Carbon::parse($request->string('date_to')->toString()),
            ];
        }

        $now = Carbon::now();

        return [
            'date_from' => $now->copy()->startOfMonth(),
            'date_to' => $now->copy()->endOfMonth(),
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    private function activeProjects(): array
    {
        return Project::query()
            ->where('is_archived', false)
            ->where('status', ProjectStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->all();
    }
}
