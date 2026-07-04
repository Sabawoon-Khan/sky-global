<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Project\Project;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PersonnelAttendanceController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $year = $request->integer('year') ?: now()->year;
        $projectId = $request->integer('project_id') ?: null;

        $attendanceRecords = PersonnelAttendance::query()
            ->where('year', $year)
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->get(['month', 'personnel_type', 'personnel_id', 'status']);

        $months = collect(range(1, 12))->map(function (int $month) use (
            $attendanceRecords,
            $year,
            $projectId,
        ): array {
            $monthRecords = $attendanceRecords->where('month', $month);
            $recordedPeople = $monthRecords
                ->unique(fn (PersonnelAttendance $record) => "{$record->personnel_type}:{$record->personnel_id}")
                ->count();

            return [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'total' => $monthRecords->count(),
                'recorded' => $recordedPeople,
                'approved' => $monthRecords->where('status', 'approved')->count(),
                'draft' => $monthRecords->where('status', 'draft')->count(),
                'missing' => $this->missingCountForPeriod($year, $month, $projectId),
                'has_records' => $monthRecords->isNotEmpty(),
            ];
        })->values();

        return Inertia::render('mis/hr/Attendance/Index', [
            'months' => $months,
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'code', 'name']),
            'summary' => [
                'year' => $year,
                'months_with_records' => $months->where('has_records', true)->count(),
                'total_records' => $attendanceRecords->count(),
                'missing_this_month' => $this->missingCountForPeriod($year, now()->month, $projectId),
            ],
            'filters' => [
                'year' => $year,
                'project_id' => $projectId,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.create');

        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;
        $projectId = $request->integer('project_id') ?: null;
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, ['employees', 'contractors'], true) ? $tab : 'employees';

        $employeeAttendances = $this->attendanceMapForPeriod(Employee::class, $year, $month, $projectId);
        $contractorAttendances = $this->attendanceMapForPeriod(Contractor::class, $year, $month, $projectId);

        $employees = Employee::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15, ['*'], 'employee_page')
            ->withQueryString()
            ->through(fn (Employee $employee) => $this->staffAttendanceRow(
                $employee,
                $employeeAttendances->get($employee->id),
            ));

        $contractors = Contractor::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15, ['*'], 'contractor_page')
            ->withQueryString()
            ->through(fn (Contractor $contractor) => $this->staffAttendanceRow(
                $contractor,
                $contractorAttendances->get($contractor->id),
            ));

        return Inertia::render('mis/hr/Attendance/Create', [
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => $employees,
            'contractors' => $contractors,
            'summary' => [
                'recorded' => $employeeAttendances->filter()->count() + $contractorAttendances->filter()->count(),
                'missing' => $this->missingCountForPeriod($year, $month, $projectId),
            ],
            'filters' => [
                'year' => $year,
                'month' => $month,
                'project_id' => $projectId,
                'tab' => $tab,
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
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'days_present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_absent' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $attributes = $this->normalizeAttendanceAttributes($validated);

        $existing = $this->findDuplicateAttendance($attributes);

        if ($existing !== null) {
            if ($this->attendanceMatchesExisting($existing, $attributes)) {
                return $this->attendanceCreatedResponse($attributes);
            }

            return $this->duplicateAttendanceResponse(
                $attributes['year'],
                $attributes['month'],
            );
        }

        try {
            $attendance = PersonnelAttendance::query()->create([
                ...$attributes,
                'status' => 'draft',
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateAttendanceException($exception)) {
                $existing = $this->findDuplicateAttendance($attributes);

                if ($existing !== null && $this->attendanceMatchesExisting($existing, $attributes)) {
                    return $this->attendanceCreatedResponse($attributes);
                }

                return $this->duplicateAttendanceResponse(
                    $attributes['year'],
                    $attributes['month'],
                );
            }

            return $this->attendanceDatabaseErrorResponse($exception);
        }

        $this->storeOptionalAttachment($request, $attendance);

        return $this->attendanceCreatedResponse($attributes);
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        if (blank($request->input('project_id'))) {
            $request->merge(['project_id' => null]);
        }

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'employee_page' => ['nullable', 'integer', 'min:1'],
            'contractor_page' => ['nullable', 'integer', 'min:1'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.personnel_id' => ['required', 'integer'],
            'entries.*.attendance_id' => ['nullable', 'integer', 'exists:personnel_attendances,id'],
            'entries.*.days_present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_absent' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.overtime_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (array_values($validated['entries']) as $entry) {
            $attributes = $this->normalizeAttendanceAttributes([
                'personnel_type' => $validated['personnel_type'],
                'personnel_id' => $entry['personnel_id'],
                'project_id' => $validated['project_id'] ?? null,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'days_present' => $entry['days_present'] ?? 0,
                'days_absent' => $entry['days_absent'] ?? 0,
                'days_leave' => $entry['days_leave'] ?? 0,
                'overtime_hours' => $entry['overtime_hours'] ?? 0,
            ]);

            if (! empty($entry['attendance_id'])) {
                $attendance = PersonnelAttendance::query()->find($entry['attendance_id']);

                if (
                    $attendance !== null
                    && $attendance->personnel_type === $validated['personnel_type']
                    && (int) $attendance->personnel_id === (int) $entry['personnel_id']
                ) {
                    $attendance->update($attributes);
                    $updated++;

                    continue;
                }
            }

            $existing = $this->findDuplicateAttendance($attributes);

            if ($existing === null) {
                $existing = $this->findAnyAttendanceForPerson(
                    $attributes['personnel_type'],
                    (int) $attributes['personnel_id'],
                    (int) $attributes['year'],
                    (int) $attributes['month'],
                );
            }

            if ($existing !== null) {
                $existing->update($attributes);
                $updated++;

                continue;
            }

            try {
                PersonnelAttendance::query()->create([
                    ...$attributes,
                    'status' => 'draft',
                ]);
                $created++;
            } catch (QueryException) {
                $skipped++;
            }
        }

        if ($created === 0 && $updated === 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No attendance records were saved. Please check the values and try again.',
            ]);

            return back()->withInput();
        }

        $message = collect([
            $created > 0 ? "{$created} created" : null,
            $updated > 0 ? "{$updated} updated" : null,
            $skipped > 0 ? "{$skipped} skipped" : null,
        ])->filter()->implode(', ');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message !== '' ? "Attendance saved ({$message})." : 'Attendance saved.',
        ]);

        $tab = $validated['personnel_type'] === Employee::class ? 'employees' : 'contractors';

        return redirect()->route('hr.attendance.create', array_filter([
            'year' => $validated['year'],
            'month' => $validated['month'],
            'project_id' => $validated['project_id'] ?? null,
            'tab' => $tab,
            'employee_page' => $validated['employee_page'] ?? null,
            'contractor_page' => $validated['contractor_page'] ?? null,
        ]));
    }

    public function update(Request $request, PersonnelAttendance $attendance): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'days_present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_absent' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $attributes = $this->normalizeAttendanceAttributes([
            ...$attendance->only([
                'personnel_type',
                'personnel_id',
                'project_id',
                'project_site_id',
                'year',
                'month',
            ]),
            ...$validated,
        ]);

        if ($this->findDuplicateAttendance($attributes, $attendance->id)) {
            return $this->duplicateAttendanceResponse(
                $attributes['year'],
                $attributes['month'],
            );
        }

        try {
            $attendance->update($attributes);
        } catch (QueryException $exception) {
            if ($this->isDuplicateAttendanceException($exception)) {
                return $this->duplicateAttendanceResponse(
                    $attributes['year'],
                    $attributes['month'],
                );
            }

            return $this->attendanceDatabaseErrorResponse($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Attendance record updated.',
        ]);

        return back();
    }

    public function approve(Request $request, PersonnelAttendance $attendance): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $attendance->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Attendance approved.',
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAttendanceAttributes(array $validated): array
    {
        return [
            ...$validated,
            'days_present' => (int) ($validated['days_present'] ?? 0),
            'days_absent' => (int) ($validated['days_absent'] ?? 0),
            'days_leave' => (int) ($validated['days_leave'] ?? 0),
            'overtime_hours' => round((float) ($validated['overtime_hours'] ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function findDuplicateAttendance(array $attributes, ?int $exceptId = null): ?PersonnelAttendance
    {
        $query = PersonnelAttendance::query()
            ->where('personnel_type', $attributes['personnel_type'])
            ->where('personnel_id', $attributes['personnel_id'])
            ->where('year', $attributes['year'])
            ->where('month', $attributes['month']);

        if (($attributes['project_id'] ?? null) === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $attributes['project_id']);
        }

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->first();
    }

    private function findAnyAttendanceForPerson(
        string $personnelType,
        int $personnelId,
        int $year,
        int $month,
    ): ?PersonnelAttendance {
        return PersonnelAttendance::query()
            ->where('personnel_type', $personnelType)
            ->where('personnel_id', $personnelId)
            ->where('year', $year)
            ->where('month', $month)
            ->orderByRaw('project_id is null desc')
            ->orderByDesc('id')
            ->first();
    }

    private function missingCountForPeriod(int $year, int $month, ?int $projectId): int
    {
        if ($projectId !== null) {
            return count($this->missingPersonnelIds(Employee::class, $year, $month, $projectId))
                + count($this->missingPersonnelIds(Contractor::class, $year, $month, $projectId));
        }

        $recordedKeys = PersonnelAttendance::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get(['personnel_type', 'personnel_id'])
            ->map(fn (PersonnelAttendance $record) => "{$record->personnel_type}:{$record->personnel_id}")
            ->unique()
            ->values()
            ->all();

        $missingEmployees = Employee::query()
            ->where('status', 'active')
            ->pluck('id')
            ->reject(fn (int $id) => in_array(Employee::class.":{$id}", $recordedKeys, true))
            ->count();

        $missingContractors = Contractor::query()
            ->where('status', 'active')
            ->pluck('id')
            ->reject(fn (int $id) => in_array(Contractor::class.":{$id}", $recordedKeys, true))
            ->count();

        return $missingEmployees + $missingContractors;
    }

    private function duplicateAttendanceResponse(int $year, int $month): RedirectResponse
    {
        $periodLabel = Carbon::create($year, $month, 1)->format('F')." {$year}";

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => "An attendance record for this person and project already exists for {$periodLabel}.",
        ]);

        return back()->withInput();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attendanceCreatedResponse(array $attributes): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Attendance record created.',
        ]);

        return redirect()->route('hr.attendance.index', array_filter([
            'year' => $attributes['year'],
            'project_id' => $attributes['project_id'] ?? null,
        ]));
    }

    private function personnelDisplayName(PersonnelAttendance $attendance): ?string
    {
        $personnel = $attendance->personnel;

        if ($personnel === null) {
            return null;
        }

        return trim("{$personnel->first_name} {$personnel->last_name}");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attendanceMatchesExisting(
        PersonnelAttendance $existing,
        array $attributes,
    ): bool {
        return (int) $existing->days_present === (int) $attributes['days_present']
            && (int) $existing->days_absent === (int) $attributes['days_absent']
            && (int) $existing->days_leave === (int) $attributes['days_leave']
            && round((float) $existing->overtime_hours, 2) === round((float) $attributes['overtime_hours'], 2)
            && ($existing->notes ?? '') === ($attributes['notes'] ?? '')
            && ($existing->project_site_id ?? null) == ($attributes['project_site_id'] ?? null);
    }

    private function attendanceDatabaseErrorResponse(
        QueryException $exception,
    ): RedirectResponse {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Could not save attendance. Please check the values and try again.',
        ]);

        return back()->withInput();
    }

    private function isDuplicateAttendanceException(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'personnel_attendance_unique');
    }

    /**
     * @return Collection<int, PersonnelAttendance>
     */
    private function attendanceMapForPeriod(
        string $personnelType,
        int $year,
        int $month,
        ?int $projectId,
    ): Collection {
        return PersonnelAttendance::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('personnel_type', $personnelType)
            ->get()
            ->groupBy('personnel_id')
            ->map(fn (Collection $records) => $this->pickAttendanceForProject($records, $projectId));
    }

    /**
     * @param  Collection<int, PersonnelAttendance>  $records
     */
    private function pickAttendanceForProject(
        Collection $records,
        ?int $projectId,
    ): ?PersonnelAttendance {
        if ($projectId !== null) {
            $match = $records->firstWhere('project_id', $projectId);

            if ($match !== null) {
                return $match;
            }
        }

        $withoutProject = $records->firstWhere('project_id', null);

        if ($withoutProject !== null) {
            return $withoutProject;
        }

        return $records->sortByDesc('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function staffAttendanceRow(Employee|Contractor $person, ?PersonnelAttendance $attendance): array
    {
        return [
            'id' => $person->id,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'attendance_id' => $attendance?->id,
            'days_present' => $attendance?->days_present ?? 0,
            'days_absent' => $attendance?->days_absent ?? 0,
            'days_leave' => $attendance?->days_leave ?? 0,
            'overtime_hours' => $attendance?->overtime_hours ?? 0,
            'status' => $attendance?->status,
        ];
    }

    /**
     * @return list<int>
     */
    private function missingPersonnelIds(
        string $personnelType,
        int $year,
        int $month,
        ?int $projectId,
    ): array {
        $recordedIds = PersonnelAttendance::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('personnel_type', $personnelType)
            ->when(
                $projectId,
                fn ($query) => $query->where('project_id', $projectId),
                fn ($query) => $query->whereNull('project_id'),
            )
            ->pluck('personnel_id');

        $model = $personnelType === Employee::class ? Employee::class : Contractor::class;

        return $model::query()
            ->where('status', 'active')
            ->whereNotIn('id', $recordedIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->pluck('id')
            ->all();
    }
}
