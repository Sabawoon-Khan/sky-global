<?php

namespace App\Http\Controllers\Training;

use App\Enums\TrainingStatus;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Training\TrainingGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingGuardConversionController extends Controller
{
    use AuthorizesMisPermissions;

    public function toEmployee(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        if (! in_array($trainingGuard->status, [TrainingStatus::Completed->value, TrainingStatus::Certified->value], true)) {
            return back()->with('error', __('Only guards who have completed training can be hired.'));
        }

        if ($trainingGuard->employee_id || $trainingGuard->contractor_id) {
            return back()->with('error', __('This guard is already linked to HR.'));
        }

        $existing = Employee::query()
            ->when($trainingGuard->tazkira_number, fn ($q, string $t) => $q->where('tazkira_number', $t))
            ->first();

        if ($existing) {
            $trainingGuard->update(['employee_id' => $existing->id]);

            return redirect()
                ->route('hr.employees.show', $existing)
                ->with('success', __('Linked to existing employee.'));
        }

        $employee = Employee::query()->create([
            'first_name' => $trainingGuard->name,
            'last_name' => $trainingGuard->father_name,
            'father_name' => $trainingGuard->father_name,
            'tazkira_number' => $trainingGuard->tazkira_number,
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $trainingGuard->update(['employee_id' => $employee->id]);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', __('Guard converted to employee.'));
    }

    public function toContractor(Request $request, TrainingGuard $trainingGuard): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        if (! in_array($trainingGuard->status, [TrainingStatus::Completed->value, TrainingStatus::Certified->value], true)) {
            return back()->with('error', __('Only guards who have completed training can be hired.'));
        }

        if ($trainingGuard->employee_id || $trainingGuard->contractor_id) {
            return back()->with('error', __('This guard is already linked to HR.'));
        }

        $existing = Contractor::query()
            ->when($trainingGuard->tazkira_number, fn ($q, string $t) => $q->where('tazkira_number', $t))
            ->first();

        if ($existing) {
            $trainingGuard->update(['contractor_id' => $existing->id]);

            return redirect()
                ->route('hr.contractors.show', $existing)
                ->with('success', __('Linked to existing contractor.'));
        }

        $contractor = Contractor::query()->create([
            'first_name' => $trainingGuard->name,
            'last_name' => $trainingGuard->father_name,
            'father_name' => $trainingGuard->father_name,
            'tazkira_number' => $trainingGuard->tazkira_number,
            'status' => 'active',
        ]);

        $trainingGuard->update(['contractor_id' => $contractor->id]);

        return redirect()
            ->route('hr.contractors.show', $contractor)
            ->with('success', __('Guard converted to contractor.'));
    }
}
