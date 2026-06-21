<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalary;
use App\Models\Hr\PayrollItem;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use AuthorizesMisPermissions;

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

        $payrollRun = PayrollRun::query()->create([
            ...$validated,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('hr.payroll.show', $payrollRun)
            ->with('success', 'Payroll run created.');
    }

    public function show(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $payrollRun->load(['items.project', 'processedBy']);

        return Inertia::render('mis/hr/Payroll/Show', [
            'payrollRun' => $payrollRun,
        ]);
    }

    public function process(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        if ($payrollRun->status === 'processed') {
            return back()->withErrors(['payroll' => 'Payroll run is already processed.']);
        }

        DB::transaction(function () use ($payrollRun, $request) {
            $payrollRun->items()->delete();

            $attendances = PersonnelAttendance::query()
                ->where('year', $payrollRun->period_year)
                ->where('month', $payrollRun->period_month)
                ->where('status', 'approved')
                ->get();

            foreach ($attendances as $attendance) {
                $baseAmount = $this->calculateBaseAmount($attendance);

                PayrollItem::query()->create([
                    'payroll_run_id' => $payrollRun->id,
                    'personnel_type' => $attendance->personnel_type,
                    'personnel_id' => $attendance->personnel_id,
                    'project_id' => $attendance->project_id,
                    'base_amount' => $baseAmount,
                    'deductions' => 0,
                    'net_amount' => $baseAmount,
                    'currency' => 'USD',
                    'notes' => "Generated from attendance #{$attendance->id}",
                ]);
            }

            $payrollRun->update([
                'status' => 'processed',
                'processed_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Payroll run processed.');
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
