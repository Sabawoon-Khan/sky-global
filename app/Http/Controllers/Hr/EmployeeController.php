<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Concerns\StoresPersonnelAttachments;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Forms\AttachmentType;
use App\Models\Hr\Employee;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Hr\PersonnelPayrollAdjustment;
use App\Models\Project\ProjectDeployment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments, StoresPersonnelAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $employees = Employee::query()
            ->with(['jobDetails.department'])
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        $employees->getCollection()->transform(function (Employee $employee) {
            $employee->setAttribute(
                'job_detail',
                $employee->jobDetails->sortByDesc('id')->first(),
            );

            return $employee;
        });

        return Inertia::render('mis/hr/Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.create');

        return Inertia::render('mis/hr/Employees/Create', [
            'departments' => Department::query()->orderBy('name')->get(),
            'attachmentTypes' => $this->activeAttachmentTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'original_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'string', 'in:active,inactive,terminated'],
            'job_detail' => ['nullable', 'array'],
            'job_detail.department_id' => ['nullable', 'exists:departments,id'],
            'job_detail.designation' => ['nullable', 'string', 'max:100'],
            'job_detail.hire_date' => ['nullable', 'date'],
            'job_detail.salary_grade' => ['nullable', 'string', 'max:50'],
            ...$this->personnelAttachmentValidationRules(),
        ]);

        $jobDetail = $validated['job_detail'] ?? null;
        unset($validated['job_detail'], $validated['personnel_forms']);

        $employee = Employee::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);

        if (is_array($jobDetail)) {
            $employee->jobDetails()->create($jobDetail);
        }
        $this->storeOptionalAttachment($request, $employee);
        $this->storePersonnelAttachments($request, $employee, 'employee');

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', 'Employee created.');
    }

    public function show(Request $request, Employee $employee): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $employee->load([
            'jobDetails.department',
            'salaries',
            'contracts',
            'user',
            'attachments',
            'personnelAttachments.attachmentType',
        ]);

        $employee->setAttribute(
            'job_detail',
            $employee->jobDetails->sortByDesc('id')->first(),
        );

        return Inertia::render('mis/hr/Employees/Show', [
            'employee' => $employee,
            'departments' => Department::query()->orderBy('name')->get(),
            'attachmentTypes' => $this->activeAttachmentTypes(),
            'attendances' => PersonnelAttendance::query()
                ->where('personnel_type', Employee::class)
                ->where('personnel_id', $employee->id)
                ->with('project')
                ->latest()
                ->limit(24)
                ->get(),
            'payrollAdjustments' => PersonnelPayrollAdjustment::query()
                ->where('personnel_type', Employee::class)
                ->where('personnel_id', $employee->id)
                ->with('project')
                ->latest()
                ->limit(24)
                ->get(),
            'deployments' => ProjectDeployment::query()
                ->where('personnel_type', Employee::class)
                ->where('personnel_id', $employee->id)
                ->with('project')
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Request $request, Employee $employee): Response
    {
        $this->authorizePermission($request, 'hr.edit');

        $employee->load(['jobDetails.department', 'personnelAttachments.attachmentType']);

        $employee->setAttribute(
            'job_detail',
            $employee->jobDetails->sortByDesc('id')->first(),
        );

        return Inertia::render('mis/hr/Employees/Edit', [
            'employee' => $employee,
            'departments' => Department::query()->orderBy('name')->get(),
            'attachmentTypes' => $this->activeAttachmentTypes(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'original_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'string', 'in:active,inactive,terminated'],
            'job_detail' => ['nullable', 'array'],
            'job_detail.department_id' => ['nullable', 'exists:departments,id'],
            'job_detail.designation' => ['nullable', 'string', 'max:100'],
            'job_detail.hire_date' => ['nullable', 'date'],
            'job_detail.salary_grade' => ['nullable', 'string', 'max:50'],
            ...$this->personnelAttachmentValidationRules(),
        ]);

        $jobDetail = $validated['job_detail'] ?? null;
        unset($validated['job_detail'], $validated['personnel_forms']);

        $employee->update($validated);

        if (is_array($jobDetail)) {
            $employee->jobDetails()->updateOrCreate(
                ['employee_id' => $employee->id],
                $jobDetail,
            );
        }
        $this->storeOptionalAttachment($request, $employee);
        $this->storePersonnelAttachments($request, $employee, 'employee');

        if (array_keys($validated) === ['status']) {
            return back()->with('success', 'Employee status updated.');
        }

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AttachmentType>
     */
    private function activeAttachmentTypes()
    {
        return AttachmentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'requires_expiry', 'sort_order']);
    }
}
