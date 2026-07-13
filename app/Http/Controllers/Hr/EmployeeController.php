<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Concerns\StoresPersonnelAttachments;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Finance\Currency;
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
            'currencies' => $this->activeCurrencies(),
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
            'is_permanent' => ['nullable', 'boolean'],
            'job_detail' => ['nullable', 'array'],
            'job_detail.department_id' => ['nullable', 'exists:departments,id'],
            'job_detail.designation' => ['nullable', 'string', 'max:100'],
            'job_detail.hire_date' => ['nullable', 'date'],
            'job_detail.salary_grade' => ['nullable', 'string', 'max:50'],
            ...$this->salaryValidationRules(),
            ...$this->personnelAttachmentValidationRules(),
        ]);

        $jobDetail = $validated['job_detail'] ?? null;
        unset($validated['job_detail'], $validated['salaries'], $validated['personnel_forms']);

        $employee = Employee::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
            'is_permanent' => $validated['is_permanent'] ?? false,
        ]);
        $employee->logStatusChange($employee->status, null, $request->user());

        if (is_array($jobDetail)) {
            $employee->jobDetails()->create($jobDetail);
        }
        $this->storeOptionalAttachment($request, $employee);
        $this->syncSalaries($request, $employee);
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
            'salaries' => fn ($q) => $q->orderByDesc('effective_from'),
            'contracts',
            'user',
            'attachments',
            'personnelAttachments.attachmentType',
            'statusChangeLogs' => fn ($q) => $q->with('changedBy:id,name')->latest(),
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

        $employee->load([
            'jobDetails.department',
            'salaries' => fn ($q) => $q->orderByDesc('effective_from'),
            'personnelAttachments.attachmentType',
        ]);

        $employee->setAttribute(
            'job_detail',
            $employee->jobDetails->sortByDesc('id')->first(),
        );

        return Inertia::render('mis/hr/Employees/Edit', [
            'employee' => $employee,
            'departments' => Department::query()->orderBy('name')->get(),
            'currencies' => $this->activeCurrencies(),
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
            'is_permanent' => ['nullable', 'boolean'],
            'job_detail' => ['nullable', 'array'],
            'job_detail.department_id' => ['nullable', 'exists:departments,id'],
            'job_detail.designation' => ['nullable', 'string', 'max:100'],
            'job_detail.hire_date' => ['nullable', 'date'],
            'job_detail.salary_grade' => ['nullable', 'string', 'max:50'],
            ...$this->salaryValidationRules(true),
            ...$this->personnelAttachmentValidationRules(),
        ]);

        $jobDetail = $validated['job_detail'] ?? null;
        unset($validated['job_detail'], $validated['salaries'], $validated['personnel_forms']);

        $oldStatus = $employee->status;

        $employee->update($validated);

        if (array_key_exists('status', $validated) && $validated['status'] !== $oldStatus) {
            $employee->logStatusChange($validated['status'], $oldStatus, $request->user());
        }

        if (is_array($jobDetail)) {
            $employee->jobDetails()->updateOrCreate(
                ['employee_id' => $employee->id],
                $jobDetail,
            );
        }
        $this->storeOptionalAttachment($request, $employee);
        $this->syncSalaries($request, $employee);
        $this->storePersonnelAttachments($request, $employee, 'employee');

        if (array_keys($validated) === ['status']) {
            return back()->with('success', 'Employee status updated.');
        }

        if (array_keys($validated) === ['is_permanent']) {
            return back()->with('success', 'Employee employment type updated.');
        }

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function salaryValidationRules(bool $updating = false): array
    {
        $rules = [
            'salaries' => ['nullable', 'array'],
            'salaries.*.amount' => ['nullable', 'numeric', 'min:0'],
            'salaries.*.currency' => ['nullable', 'string', 'size:3'],
            'salaries.*.effective_from' => ['nullable', 'date'],
            'salaries.*.effective_to' => ['nullable', 'date'],
            'salaries.*.notes' => ['nullable', 'string'],
        ];

        if ($updating) {
            $rules['salaries.*.id'] = ['nullable', 'exists:employee_salaries,id'];
        }

        return $rules;
    }

    private function syncSalaries(Request $request, Employee $employee): void
    {
        if (! $request->has('salaries_synced')) {
            return;
        }

        if (! $employee->is_permanent) {
            $employee->salaries()->delete();

            return;
        }

        $salaries = $request->input('salaries', []);

        if (! is_array($salaries)) {
            return;
        }

        $keptIds = [];

        foreach ($salaries as $salaryData) {
            if (($salaryData['amount'] ?? null) === null || ($salaryData['amount'] ?? '') === '') {
                continue;
            }

            if (empty($salaryData['effective_from'])) {
                continue;
            }

            $payload = [
                'amount' => $salaryData['amount'],
                'currency' => $salaryData['currency'] ?? 'AFN',
                'effective_from' => $salaryData['effective_from'],
                'effective_to' => $salaryData['effective_to'] ?? null,
                'notes' => $salaryData['notes'] ?? null,
            ];

            if (! empty($salaryData['id'])) {
                $salary = $employee->salaries()->find($salaryData['id']);

                if ($salary) {
                    $salary->update($payload);
                    $keptIds[] = $salary->id;
                }

                continue;
            }

            $created = $employee->salaries()->create($payload);
            $keptIds[] = $created->id;
        }

        if ($keptIds !== []) {
            $employee->salaries()->whereNotIn('id', $keptIds)->delete();
        } else {
            $employee->salaries()->delete();
        }
    }

    /**
     * @return list<string>
     */
    private function activeCurrencies(): array
    {
        return Currency::query()
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->pluck('code')
            ->all() ?: ['AFN'];
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
