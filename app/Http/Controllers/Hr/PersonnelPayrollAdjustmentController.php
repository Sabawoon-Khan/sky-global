<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PersonnelPayrollAdjustment;
use App\Models\Project\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PersonnelPayrollAdjustmentController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;

        $adjustments = PersonnelPayrollAdjustment::query()
            ->with(['project', 'personnel'])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('mis/hr/PayrollAdjustments/Index', [
            'adjustments' => $adjustments,
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'contractors' => Contractor::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'adjustmentTypes' => $this->adjustmentTypeOptions(),
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'personnel_id' => ['required', 'integer'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'type' => ['required', Rule::in([
                PersonnelPayrollAdjustment::TYPE_SALARY,
                PersonnelPayrollAdjustment::TYPE_BONUS,
                PersonnelPayrollAdjustment::TYPE_DEDUCTION,
                PersonnelPayrollAdjustment::TYPE_ADVANCE,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        PersonnelPayrollAdjustment::query()->create([
            ...$validated,
            'amount' => round((float) $validated['amount'], 2),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payroll adjustment recorded.',
        ]);

        return redirect()->route('hr.payroll-adjustments.index', [
            'year' => $validated['period_year'],
            'month' => $validated['period_month'],
        ]);
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'type' => ['required', Rule::in([
                PersonnelPayrollAdjustment::TYPE_SALARY,
                PersonnelPayrollAdjustment::TYPE_BONUS,
                PersonnelPayrollAdjustment::TYPE_DEDUCTION,
                PersonnelPayrollAdjustment::TYPE_ADVANCE,
            ])],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.personnel_id' => ['required', 'integer'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = 0;
        $entries = array_values($validated['entries']);

        foreach ($entries as $entry) {
            PersonnelPayrollAdjustment::query()->create([
                'personnel_type' => $validated['personnel_type'],
                'personnel_id' => $entry['personnel_id'],
                'project_id' => $validated['project_id'] ?? null,
                'period_year' => $validated['period_year'],
                'period_month' => $validated['period_month'],
                'type' => $validated['type'],
                'amount' => round((float) $entry['amount'], 2),
                'notes' => $entry['notes'] ?? null,
            ]);
            $created++;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$created} payroll adjustments recorded.",
        ]);

        return redirect()->route('hr.payroll-adjustments.index', [
            'year' => $validated['period_year'],
            'month' => $validated['period_month'],
        ]);
    }

    public function destroy(Request $request, PersonnelPayrollAdjustment $payrollAdjustment): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.delete');

        if ($payrollAdjustment->applied_at !== null) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Applied adjustments cannot be deleted.',
            ]);

            return back();
        }

        $year = $payrollAdjustment->period_year;
        $month = $payrollAdjustment->period_month;

        $payrollAdjustment->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payroll adjustment removed.',
        ]);

        return redirect()->route('hr.payroll-adjustments.index', [
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function adjustmentTypeOptions(): array
    {
        return [
            ['value' => PersonnelPayrollAdjustment::TYPE_SALARY, 'label' => 'Salary'],
            ['value' => PersonnelPayrollAdjustment::TYPE_BONUS, 'label' => 'Bonus'],
            ['value' => PersonnelPayrollAdjustment::TYPE_DEDUCTION, 'label' => 'Deduction'],
            ['value' => PersonnelPayrollAdjustment::TYPE_ADVANCE, 'label' => 'Advance'],
        ];
    }
}
