<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalary;
use App\Models\Hr\PayrollItem;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Hr\PersonnelPayrollAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $payrollRuns = PayrollRun::query()
            ->with('processedBy')
            ->withCount('items')
            ->latest('period_year')
            ->latest('period_month')
            ->paginate(15);

        return Inertia::render('mis/hr/Payroll/Index', [
            'payrollRuns' => $payrollRuns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $existing = PayrollRun::query()
            ->where('period_year', $validated['period_year'])
            ->where('period_month', $validated['period_month'])
            ->first();

        if ($existing) {
            return $this->duplicatePayrollPeriodResponse(
                $validated['period_year'],
                $validated['period_month'],
            );
        }

        $payrollRun = PayrollRun::query()->create([
            ...$validated,
            'status' => 'draft',
        ]);
        $this->storeOptionalAttachment($request, $payrollRun);

        return redirect()
            ->route('hr.payroll.show', $payrollRun)
            ->with('success', 'Payroll run created.');
    }

    private function duplicatePayrollPeriodResponse(int $year, int $month): RedirectResponse
    {
        $periodLabel = Carbon::create($year, $month, 1)->format('F')." {$year}";

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => "A payroll run for {$periodLabel} already exists.",
        ]);

        return back()->withInput();
    }

    public function show(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $payrollRun->load(['items.project', 'items.personnel', 'processedBy', 'attachments']);

        $approvedAttendanceCount = PersonnelAttendance::query()
            ->where('year', $payrollRun->period_year)
            ->where('month', $payrollRun->period_month)
            ->where('status', 'approved')
            ->count();

        $pendingAdjustments = PersonnelPayrollAdjustment::query()
            ->pending()
            ->with(['project', 'personnel'])
            ->where('period_year', $payrollRun->period_year)
            ->where('period_month', $payrollRun->period_month)
            ->latest()
            ->get();

        return Inertia::render('mis/hr/Payroll/Show', [
            'payrollRun' => $payrollRun,
            'approvedAttendanceCount' => $approvedAttendanceCount,
            'pendingAdjustments' => $pendingAdjustments,
        ]);
    }

    public function process(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        if ($payrollRun->status === 'processed') {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Payroll run is already processed.',
            ]);

            return back();
        }

        $itemCount = 0;

        DB::transaction(function () use ($payrollRun, $request, &$itemCount) {
            $payrollRun->items()->delete();

            $attendances = PersonnelAttendance::query()
                ->where('year', $payrollRun->period_year)
                ->where('month', $payrollRun->period_month)
                ->where('status', 'approved')
                ->get();

            foreach ($attendances as $attendance) {
                $baseAmount = max(
                    $this->calculateBaseAmount($attendance),
                    0.0,
                );
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

                $item = PayrollItem::query()->create([
                    'payroll_run_id' => $payrollRun->id,
                    'personnel_type' => $attendance->personnel_type,
                    'personnel_id' => $attendance->personnel_id,
                    'project_id' => $attendance->project_id,
                    'base_amount' => $baseAmount,
                    'bonus' => $adjustments['bonus'],
                    'deductions' => $adjustments['deductions'],
                    'advance' => $adjustments['advance'],
                    'net_amount' => PayrollItem::calculateNetAmount(
                        $baseAmount,
                        $adjustments['bonus'],
                        $adjustments['deductions'],
                        $adjustments['advance'],
                    ),
                    'currency' => 'USD',
                    'notes' => "Generated from attendance #{$attendance->id}",
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
                    'currency' => 'USD',
                    'notes' => 'Generated from salary adjustment',
                ]);

                PersonnelPayrollAdjustment::markAppliedForPayrollItem(
                    $item,
                    $payrollRun->period_year,
                    $payrollRun->period_month,
                );

                $itemCount++;
            }

            $payrollRun->update([
                'status' => 'processed',
                'processed_by' => $request->user()->id,
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $itemCount > 0
                ? "Payroll processed with {$itemCount} line items."
                : 'Payroll processed, but no approved attendance was found for this period.',
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

    private function calculateBaseAmount(PersonnelAttendance $attendance): float
    {
        if ($attendance->personnel_type === Employee::class) {
            $salary = EmployeeSalary::query()
                ->where('employee_id', $attendance->personnel_id)
                ->where(function ($q) use ($attendance) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', "{$attendance->year}-{$attendance->month}-01");
                })
                ->orderByDesc('effective_from')
                ->value('amount');

            if ($salary) {
                return round((float) $salary * ($attendance->days_present / 30), 2);
            }
        }

        return round((float) $attendance->days_present * 100, 2);
    }
}
