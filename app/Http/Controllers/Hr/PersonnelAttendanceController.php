<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Enums\ProjectStatus;
use App\Models\Hr\AttendanceSheet;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Project\Project;
use App\Models\Project\ProjectDeployment;
use App\Support\DailyAttendanceMarks;
use Carbon\CarbonInterface;
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

        $period = $this->resolvePeriodFromRequest($request);
        $year = $request->filled('year') ? $request->integer('year') : null;
        $month = $request->filled('month') ? $request->integer('month') : null;
        $projectId = $request->integer('project_id') ?: null;

        $sheetsQuery = AttendanceSheet::query()
            ->with(['project', 'creator'])
            ->withCount([
                'attendances',
                'attendances as draft_count' => fn ($query) => $query->where('status', 'draft'),
                'attendances as submitted_count' => fn ($query) => $query->where('status', 'submitted'),
                'attendances as approved_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderByDesc('updated_at');

        if ($year !== null) {
            $sheetsQuery->where('year', $year);
        }

        if ($month !== null) {
            $sheetsQuery->where('month', $month);
        }

        if ($projectId !== null) {
            $sheetsQuery->where('project_id', $projectId);
        }

        $sheets = $sheetsQuery
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AttendanceSheet $sheet) => [
                'id' => $sheet->id,
                'title' => $sheet->title,
                'attendance_type' => $sheet->attendance_type,
                'project' => $sheet->project?->only(['id', 'code', 'name']),
                'date_from' => $sheet->date_from->toDateString(),
                'date_to' => $sheet->date_to->toDateString(),
                'year' => $sheet->year,
                'month' => $sheet->month,
                'staff_count' => $sheet->attendances_count,
                'status' => $this->resolveSheetStatus(
                    (int) $sheet->attendances_count,
                    (int) $sheet->draft_count,
                    (int) $sheet->submitted_count,
                    (int) $sheet->approved_count,
                ),
                'can_delete' => (int) $sheet->submitted_count === 0
                    && (int) $sheet->approved_count === 0,
                'created_by_name' => $sheet->creator?->name,
                'updated_at' => $sheet->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('mis/hr/Attendance/Index', [
            'sheets' => $sheets,
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

    private function resolveSheetStatus(
        int $total,
        int $draft,
        int $submitted,
        int $approved,
    ): string {
        if ($total === 0 || $draft === $total) {
            return 'draft';
        }

        if ($approved === $total) {
            return 'approved';
        }

        if (($submitted + $approved) === $total) {
            return 'submitted';
        }

        return 'partial';
    }

    public function print(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $sheetId = $request->integer('sheet_id') ?: null;

        if ($sheetId !== null) {
            return $this->printAttendanceSheet($sheetId);
        }

        $period = $this->resolvePeriodFromRequest($request);
        $year = $period['year'];
        $month = $period['month'];
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];
        $projectId = $request->integer('project_id') ?: null;

        $periodStart = Carbon::create($year, $month, 1);
        $daysInMonth = $periodStart->daysInMonth;

        $employeeAttendances = $this->attendanceMapForPeriod(Employee::class, $year, $month, $projectId);
        $contractorAttendances = $this->attendanceMapForPeriod(Contractor::class, $year, $month, $projectId);

        $employees = $this
            ->activePersonnelQuery(Employee::class, $projectId, $dateFrom, $dateTo)
            ->with(['jobDetails' => fn ($query) => $query->orderByDesc('hire_date')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee) => $this->staffPrintRow(
                $employee,
                $employeeAttendances->get($employee->id),
                $daysInMonth,
            ))
            ->values();

        $contractors = $this
            ->activePersonnelQuery(Contractor::class, $projectId, $dateFrom, $dateTo)
            ->with(['agreements' => fn ($query) => $query->orderByDesc('start_date')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Contractor $contractor) => $this->staffPrintRow(
                $contractor,
                $contractorAttendances->get($contractor->id),
                $daysInMonth,
            ))
            ->values();

        return $this->renderPrintView(
            $employees->concat($contractors)->values(),
            $periodStart,
            $dateFrom,
            $dateTo,
            $projectId,
            null,
        );
    }

    public function destroySheet(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.delete');

        $hasLockedRecords = $sheet->attendances()
            ->whereIn('status', ['submitted', 'approved'])
            ->exists();

        if ($hasLockedRecords) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Submitted or approved attendance sheets cannot be deleted.',
            ]);

            return back();
        }

        $sheet->attendances()->delete();
        $sheet->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Attendance sheet deleted.',
        ]);

        return redirect()->route('hr.attendance.index');
    }

    private function printAttendanceSheet(int $sheetId): Response
    {
        $sheet = AttendanceSheet::query()
            ->with(['project', 'attendances'])
            ->findOrFail($sheetId);

        $dateFrom = $sheet->date_from->copy();
        $dateTo = $sheet->date_to->copy();
        $periodStart = Carbon::create($sheet->year, $sheet->month, 1);
        $daysInMonth = $periodStart->daysInMonth;

        $attendanceByPerson = $sheet->attendances->keyBy(
            fn (PersonnelAttendance $attendance) => "{$attendance->personnel_type}:{$attendance->personnel_id}",
        );

        $employeeIds = $sheet->attendances
            ->where('personnel_type', Employee::class)
            ->pluck('personnel_id');

        $contractorIds = $sheet->attendances
            ->where('personnel_type', Contractor::class)
            ->pluck('personnel_id');

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->with(['jobDetails' => fn ($query) => $query->orderByDesc('hire_date')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee) => $this->staffPrintRow(
                $employee,
                $attendanceByPerson->get(Employee::class.':'.$employee->id),
                $daysInMonth,
            ))
            ->values();

        $contractors = Contractor::query()
            ->whereIn('id', $contractorIds)
            ->with(['agreements' => fn ($query) => $query->orderByDesc('start_date')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Contractor $contractor) => $this->staffPrintRow(
                $contractor,
                $attendanceByPerson->get(Contractor::class.':'.$contractor->id),
                $daysInMonth,
            ))
            ->values();

        return $this->renderPrintView(
            $employees->concat($contractors)->values(),
            $periodStart,
            $dateFrom,
            $dateTo,
            $sheet->project_id,
            $sheet,
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $staff
     */
    private function renderPrintView(
        Collection $staff,
        Carbon $periodStart,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $projectId,
        ?AttendanceSheet $sheet,
    ): Response {
        $project = $projectId !== null
            ? Project::query()->find($projectId, ['id', 'code', 'name', 'location'])
            : null;

        $calendarDays = $this->calendarDaysForRange($periodStart, $dateFrom, $dateTo);

        $ssmName = $staff
            ->first(
                fn (array $row) => str_contains(strtolower((string) ($row['designation'] ?? '')), 'ssm'),
            )['name'] ?? null;

        $sheetTitle = $sheet?->title ?? (
            $project !== null
                ? "{$project->code} Security Team Attendance Sheet For {$periodStart->format('F-Y')}"
                : "Security Team Attendance Sheet For {$periodStart->format('F-Y')}"
        );

        return Inertia::render('mis/hr/Attendance/Print', [
            'staff' => $staff,
            'calendar_days' => $calendarDays,
            'project' => $project,
            'sheet_title' => $sheetTitle,
            'location' => $project?->location,
            'ssm_name' => $ssmName,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'project_id' => $projectId,
                'sheet_id' => $sheet?->id,
            ],
            'period_label' => $this->periodLabel($dateFrom, $dateTo),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.create');

        $period = $this->resolvePeriodFromRequest($request);
        $dateFrom = $period['date_from'];
        $dateTo = $period['date_to'];
        $projectId = $request->integer('project_id') ?: null;
        $sheetId = $request->integer('sheet_id') ?: null;
        $requestedType = $request->string('attendance_type')->toString();
        $attendanceType = in_array($requestedType, ['project', 'general'], true)
            ? $requestedType
            : ($projectId !== null ? 'project' : 'general');
        $titleInput = trim($request->string('title')->toString());
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, ['employees', 'contractors'], true) ? $tab : 'employees';

        if ($attendanceType === 'general') {
            $projectId = null;
        }

        $sheet = null;

        if ($sheetId !== null) {
            $existingSheet = AttendanceSheet::query()->find($sheetId);

            if ($existingSheet !== null && $this->sheetMatchesRequest(
                $existingSheet,
                $dateFrom,
                $dateTo,
                $projectId,
                $attendanceType,
            )) {
                $sheet = $existingSheet;
            }
        }

        if ($sheet === null) {
            $sheet = $this->resolveSheetForCreate(
                null,
                $dateFrom,
                $dateTo,
                $projectId,
                $attendanceType,
                $titleInput,
            );
        }

        if ($sheet !== null) {
            $dateFrom = $sheet->date_from->copy();
            $dateTo = $sheet->date_to->copy();
            $projectId = $sheet->project_id;
            $attendanceType = $sheet->attendance_type;
            $titleInput = $sheet->title;
        }

        $year = $dateFrom->year;
        $month = $dateFrom->month;
        $periodStart = Carbon::create($year, $month, 1);
        $daysInMonth = $periodStart->daysInMonth;

        $employeeAttendances = $this->attendanceMapForPeriod(Employee::class, $year, $month, $projectId, $sheet?->id);
        $contractorAttendances = $this->attendanceMapForPeriod(Contractor::class, $year, $month, $projectId, $sheet?->id);

        $perPage = $projectId !== null ? 100 : 15;

        $employees = $this
            ->activePersonnelQuery(Employee::class, $projectId, $dateFrom, $dateTo)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage, ['*'], 'employee_page')
            ->withQueryString()
            ->through(fn (Employee $employee) => $this->staffAttendanceRow(
                $employee,
                $employeeAttendances->get($employee->id),
                $daysInMonth,
            ));

        $contractors = $this
            ->activePersonnelQuery(Contractor::class, $projectId, $dateFrom, $dateTo)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage, ['*'], 'contractor_page')
            ->withQueryString()
            ->through(fn (Contractor $contractor) => $this->staffAttendanceRow(
                $contractor,
                $contractorAttendances->get($contractor->id),
                $daysInMonth,
            ));

        $calendarDays = $this->calendarDaysForRange($periodStart, $dateFrom, $dateTo);

        $submittedCount = $sheet === null
            ? 0
            : PersonnelAttendance::query()
                ->where('attendance_sheet_id', $sheet->id)
                ->where('status', 'submitted')
                ->count();

        return Inertia::render('mis/hr/Attendance/Create', [
            'projects' => $this->activeProjects(),
            'employees' => $employees,
            'contractors' => $contractors,
            'calendar_days' => $calendarDays,
            'mark_options' => DailyAttendanceMarks::allowedValues(),
            'sheet_submitted_count' => $submittedCount,
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'project_id' => $projectId,
                'sheet_id' => $sheet?->id,
                'title' => $titleInput !== '' ? $titleInput : $this->defaultSheetTitle($dateFrom, $attendanceType, $projectId),
                'attendance_type' => $attendanceType,
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
            'days_sick_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_annual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_casual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_other' => ['nullable', 'integer', 'min:0', 'max:31'],
            'daily_marks' => ['nullable', 'array'],
            'daily_marks.*' => ['nullable', 'string', 'max:2'],
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
            'sheet_id' => ['nullable', 'integer', 'exists:attendance_sheets,id'],
            'title' => ['required', 'string', 'max:255'],
            'attendance_type' => ['required', 'string', 'in:general,project'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id', 'required_if:attendance_type,project'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'action' => ['required', 'string', 'in:save,submit'],
            'employee_page' => ['nullable', 'integer', 'min:1'],
            'contractor_page' => ['nullable', 'integer', 'min:1'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.personnel_id' => ['required', 'integer'],
            'entries.*.attendance_id' => ['nullable', 'integer', 'exists:personnel_attendances,id'],
            'entries.*.days_present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_absent' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_sick_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_annual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_casual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.days_other' => ['nullable', 'integer', 'min:0', 'max:31'],
            'entries.*.daily_marks' => ['nullable', 'array'],
            'entries.*.daily_marks.*' => ['nullable', 'string', 'max:2'],
            'entries.*.overtime_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from']) : Carbon::create((int) $validated['year'], (int) $validated['month'], 1);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : $dateFrom->copy()->endOfMonth();

        $sheet = $this->resolveOrCreateSheet(
            $validated,
            $dateFrom,
            $dateTo,
            $request->user()?->id,
        );

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $locked = 0;
        $action = $validated['action'];

        foreach (array_values($validated['entries']) as $entry) {
            $attributes = $this->normalizeAttendanceAttributes([
                'personnel_type' => $validated['personnel_type'],
                'personnel_id' => $entry['personnel_id'],
                'attendance_sheet_id' => $sheet->id,
                'project_id' => $sheet->project_id,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'days_present' => $entry['days_present'] ?? 0,
                'days_absent' => $entry['days_absent'] ?? 0,
                'days_sick_leave' => $entry['days_sick_leave'] ?? 0,
                'days_annual_leave' => $entry['days_annual_leave'] ?? 0,
                'days_casual_leave' => $entry['days_casual_leave'] ?? 0,
                'days_other' => $entry['days_other'] ?? 0,
                'daily_marks' => $entry['daily_marks'] ?? null,
                'overtime_hours' => $entry['overtime_hours'] ?? 0,
            ]);

            $result = $this->persistBulkAttendanceEntry(
                $request,
                $attributes,
                $validated['personnel_type'],
                $entry,
                $action,
            );

            match ($result) {
                'created' => $created++,
                'updated' => $updated++,
                'locked' => $locked++,
                default => $skipped++,
            };
        }

        if ($created === 0 && $updated === 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $locked > 0
                    ? 'Submitted attendance cannot be changed. Contact an administrator if updates are needed.'
                    : 'No attendance records were saved. Please check the values and try again.',
            ]);

            return back()->withInput();
        }

        $message = collect([
            $created > 0 ? "{$created} created" : null,
            $updated > 0 ? "{$updated} updated" : null,
            $locked > 0 ? "{$locked} locked" : null,
            $skipped > 0 ? "{$skipped} skipped" : null,
        ])->filter()->implode(', ');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $action === 'submit'
                ? ($message !== '' ? "Attendance submitted ({$message})." : 'Attendance submitted.')
                : ($message !== '' ? "Attendance saved ({$message})." : 'Attendance saved.'),
        ]);

        $tab = $validated['personnel_type'] === Employee::class ? 'employees' : 'contractors';

        $periodStart = Carbon::create((int) $validated['year'], (int) $validated['month'], 1);

        return redirect()->route('hr.attendance.create', array_filter([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'project_id' => $sheet->project_id,
            'sheet_id' => $sheet->id,
            'title' => $sheet->title,
            'attendance_type' => $sheet->attendance_type,
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
            'days_sick_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_annual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_casual_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_other' => ['nullable', 'integer', 'min:0', 'max:31'],
            'daily_marks' => ['nullable', 'array'],
            'daily_marks.*' => ['nullable', 'string', 'max:2'],
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

    public function approveSheet(Request $request, AttendanceSheet $sheet): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $count = PersonnelAttendance::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->where('status', 'submitted')
            ->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
            ]);

        Inertia::flash('toast', [
            'type' => $count > 0 ? 'success' : 'error',
            'message' => $count > 0
                ? "Approved {$count} attendance record(s)."
                : 'No submitted records to approve on this sheet.',
        ]);

        return back();
    }

    private function resolveSheetForCreate(
        ?int $sheetId,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $projectId,
        string $attendanceType,
        string $title,
    ): ?AttendanceSheet {
        if ($sheetId !== null) {
            return AttendanceSheet::query()->find($sheetId);
        }

        $query = AttendanceSheet::query()
            ->whereDate('date_from', $dateFrom->toDateString())
            ->whereDate('date_to', $dateTo->toDateString())
            ->where('attendance_type', $attendanceType);

        if ($attendanceType === 'project') {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }

        if ($title !== '') {
            $query->where('title', $title);
        }

        return $query->latest('id')->first();
    }

    private function sheetMatchesRequest(
        AttendanceSheet $sheet,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $projectId,
        string $attendanceType,
    ): bool {
        if ($sheet->date_from->toDateString() !== $dateFrom->toDateString()) {
            return false;
        }

        if ($sheet->date_to->toDateString() !== $dateTo->toDateString()) {
            return false;
        }

        if ($sheet->attendance_type !== $attendanceType) {
            return false;
        }

        if ($attendanceType === 'project') {
            return (int) $sheet->project_id === (int) $projectId;
        }

        return $sheet->project_id === null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveOrCreateSheet(
        array $validated,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $userId,
    ): AttendanceSheet {
        $attendanceType = $validated['attendance_type'] === 'project' ? 'project' : 'general';
        $projectId = $attendanceType === 'project' ? ($validated['project_id'] ?? null) : null;

        if ($attendanceType === 'project' && $projectId === null) {
            abort(422, 'Project is required for project attendance.');
        }

        if (! empty($validated['sheet_id'])) {
            $sheet = AttendanceSheet::query()->findOrFail((int) $validated['sheet_id']);
        } else {
            $sheet = AttendanceSheet::query()->firstOrCreate(
                [
                    'title' => trim((string) $validated['title']),
                    'attendance_type' => $attendanceType,
                    'project_id' => $projectId,
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                ],
                [
                    'year' => (int) $validated['year'],
                    'month' => (int) $validated['month'],
                    'created_by' => $userId,
                ],
            );
        }

        $sheet->update([
            'title' => trim((string) $validated['title']),
            'attendance_type' => $attendanceType,
            'project_id' => $projectId,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
        ]);

        return $sheet->refresh();
    }

    private function defaultSheetTitle(
        CarbonInterface $dateFrom,
        string $attendanceType,
        ?int $projectId,
    ): string {
        $base = $attendanceType === 'project' && $projectId !== null
            ? 'Project Attendance'
            : 'General Attendance';

        return "{$base} - ".$dateFrom->format('F Y');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAttendanceAttributes(array $validated): array
    {
        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $dailyMarks = DailyAttendanceMarks::normalize(
            is_array($validated['daily_marks'] ?? null) ? $validated['daily_marks'] : null,
            $daysInMonth,
        );

        if (DailyAttendanceMarks::hasAnyMarks($dailyMarks)) {
            $totals = DailyAttendanceMarks::totalsFromMarks($dailyMarks);
            $daysPresent = $totals['days_present'];
            $daysAbsent = $totals['days_absent'];
            $daysSickLeave = $totals['days_sick_leave'];
            $daysAnnualLeave = $totals['days_annual_leave'];
            $daysCasualLeave = $totals['days_casual_leave'];
            $daysOther = $totals['days_other'];
            $daysLeave = $totals['days_leave'];
        } else {
            $daysSickLeave = (int) ($validated['days_sick_leave'] ?? 0);
            $daysAnnualLeave = (int) ($validated['days_annual_leave'] ?? 0);
            $daysCasualLeave = (int) ($validated['days_casual_leave'] ?? 0);
            $daysOther = (int) ($validated['days_other'] ?? 0);
            $daysPresent = (int) ($validated['days_present'] ?? 0);
            $daysAbsent = (int) ($validated['days_absent'] ?? 0);
            $daysLeave = $daysSickLeave + $daysAnnualLeave + $daysCasualLeave + $daysOther;
            $dailyMarks = null;
        }

        return [
            ...$validated,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'days_sick_leave' => $daysSickLeave,
            'days_annual_leave' => $daysAnnualLeave,
            'days_casual_leave' => $daysCasualLeave,
            'days_other' => $daysOther,
            'days_leave' => $daysLeave,
            'daily_marks' => $dailyMarks,
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
            ->where('personnel_id', $attributes['personnel_id']);

        if (isset($attributes['attendance_sheet_id']) && $attributes['attendance_sheet_id'] !== null) {
            $query->where('attendance_sheet_id', $attributes['attendance_sheet_id']);
        } else {
            $query->where('year', $attributes['year'])
                ->where('month', $attributes['month']);

            if (($attributes['project_id'] ?? null) === null) {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $attributes['project_id']);
            }
        }

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->first();
    }

    private function missingCountForPeriod(int $year, int $month, ?int $projectId): int
    {
        return count($this->missingPersonnelIds(Employee::class, $year, $month, $projectId))
            + count($this->missingPersonnelIds(Contractor::class, $year, $month, $projectId));
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

        $periodStart = Carbon::create((int) $attributes['year'], (int) $attributes['month'], 1);

        return redirect()->route('hr.attendance.index', array_filter([
            'date_from' => $periodStart->toDateString(),
            'date_to' => $periodStart->copy()->endOfMonth()->toDateString(),
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
            && (int) $existing->days_sick_leave === (int) $attributes['days_sick_leave']
            && (int) $existing->days_annual_leave === (int) $attributes['days_annual_leave']
            && (int) $existing->days_casual_leave === (int) $attributes['days_casual_leave']
            && (int) $existing->days_other === (int) $attributes['days_other']
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
        ?int $sheetId = null,
    ): Collection {
        $query = PersonnelAttendance::query()
            ->where('personnel_type', $personnelType);

        if ($sheetId !== null) {
            $query->where('attendance_sheet_id', $sheetId);
        } else {
            $query->where('year', $year)
                ->where('month', $month);
        }

        return $query
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
            return $records->firstWhere('project_id', $projectId);
        }

        $general = $records->firstWhere('project_id', null);

        if ($general !== null) {
            return $general;
        }

        return $records->sortByDesc('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function staffAttendanceRow(
        Employee|Contractor $person,
        ?PersonnelAttendance $attendance,
        int $daysInMonth,
    ): array {
        $dailyMarks = DailyAttendanceMarks::normalize(
            $attendance?->daily_marks,
            $daysInMonth,
        );

        return [
            'id' => $person->id,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'attendance_id' => $attendance?->id,
            'days_present' => $attendance?->days_present ?? 0,
            'days_absent' => $attendance?->days_absent ?? 0,
            'days_sick_leave' => $attendance?->days_sick_leave ?? 0,
            'days_annual_leave' => $attendance?->days_annual_leave ?? 0,
            'days_casual_leave' => $attendance?->days_casual_leave ?? 0,
            'days_other' => $attendance?->days_other ?? 0,
            'daily_marks' => $dailyMarks,
            'overtime_hours' => $attendance?->overtime_hours ?? 0,
            'status' => $attendance?->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staffPrintRow(
        Employee|Contractor $person,
        ?PersonnelAttendance $attendance,
        int $daysInMonth,
    ): array {
        $row = $this->staffAttendanceRow($person, $attendance, $daysInMonth);
        $designation = '—';
        $joiningDate = '—';

        if ($person instanceof Employee) {
            $jobDetail = $person->relationLoaded('jobDetails')
                ? $person->jobDetails->first()
                : $person->jobDetails()->orderByDesc('hire_date')->first();

            $designation = $jobDetail?->designation ?: '—';
            $joiningDate = $jobDetail?->hire_date?->format('d-M-y') ?? '—';
        } else {
            $agreement = $person->relationLoaded('agreements')
                ? $person->agreements->first()
                : $person->agreements()->orderByDesc('start_date')->first();

            $designation = 'Contractor';
            $joiningDate = $agreement?->start_date?->format('d-M-y') ?? '—';
        }

        $total = $row['days_present']
            + $row['days_absent']
            + $row['days_sick_leave']
            + $row['days_annual_leave']
            + $row['days_casual_leave']
            + $row['days_other'];

        return [
            ...$row,
            'name' => trim("{$person->first_name} {$person->last_name}"),
            'designation' => $designation,
            'joining_date' => $joiningDate,
            'remarks' => $attendance?->notes ?? '',
            'total' => $total,
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

        $query = $model::query()
            ->where('status', 'active')
            ->whereNotIn('id', $recordedIds);

        if ($personnelType === Employee::class) {
            if ($projectId) {
                $assignedIds = $this->assignedPersonnelIds(
                    $personnelType,
                    $projectId,
                    Carbon::create($year, $month, 1)->startOfMonth(),
                    Carbon::create($year, $month, 1)->endOfMonth(),
                );

                return $query
                    ->where('is_permanent', false)
                    ->whereIn('id', $assignedIds)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->pluck('id')
                    ->all();
            }

            return $query
                ->where('is_permanent', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->pluck('id')
                ->all();
        }

        return $query
            ->when(
                $projectId,
                fn ($q) => $q->whereIn(
                    'id',
                    $this->assignedPersonnelIds(
                        $personnelType,
                        $projectId,
                        Carbon::create($year, $month, 1)->startOfMonth(),
                        Carbon::create($year, $month, 1)->endOfMonth(),
                    ),
                ),
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->pluck('id')
            ->all();
    }

    /**
     * @param  class-string<Employee|Contractor>  $personnelType
     * @return \Illuminate\Database\Eloquent\Builder<Employee|Contractor>
     */
    private function activePersonnelQuery(
        string $personnelType,
        ?int $projectId,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
    ) {
        $model = $personnelType === Employee::class ? Employee::class : Contractor::class;

        $query = $model::query()->where('status', 'active');

        if ($personnelType === Employee::class) {
            if ($projectId === null) {
                return $query->where('is_permanent', true);
            }

            $assignedIds = $this->assignedPersonnelIds($personnelType, $projectId, $dateFrom, $dateTo);

            if ($assignedIds === []) {
                return $query->where('is_permanent', false)->whereRaw('0 = 1');
            }

            return $query
                ->where('is_permanent', false)
                ->whereIn('id', $assignedIds);
        }

        if ($projectId === null) {
            return $query;
        }

        $assignedIds = $this->assignedPersonnelIds($personnelType, $projectId, $dateFrom, $dateTo);

        if ($assignedIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $assignedIds);
    }

    /**
     * @return array{
     *     date_from: CarbonInterface,
     *     date_to: CarbonInterface,
     *     year: int,
     *     month: int,
     * }
     */
    private function resolvePeriodFromRequest(Request $request): array
    {
        if ($request->filled('year') && $request->filled('month')) {
            $monthStart = Carbon::create(
                $request->integer('year'),
                max(1, min(12, $request->integer('month'))),
                1,
            );

            return [
                'date_from' => $monthStart->copy(),
                'date_to' => $monthStart->copy()->endOfMonth(),
                'year' => $monthStart->year,
                'month' => $monthStart->month,
            ];
        }

        $dateFrom = $request->date('date_from') ?? now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? now()->endOfMonth();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy(), $dateFrom->copy()];
        }

        if ($dateFrom->year !== $dateTo->year || $dateFrom->month !== $dateTo->month) {
            $dateTo = $dateFrom->copy()->endOfMonth();
        }

        $monthStart = $dateFrom->copy()->startOfMonth();
        $monthEnd = $dateFrom->copy()->endOfMonth();

        if ($dateFrom->lt($monthStart)) {
            $dateFrom = $monthStart;
        }

        if ($dateTo->gt($monthEnd)) {
            $dateTo = $monthEnd;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'year' => $dateFrom->year,
            'month' => $dateFrom->month,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{day: int, weekday: string}>
     */
    private function calendarDaysForRange(
        Carbon $periodStart,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
    ): Collection {
        return collect(range($dateFrom->day, $dateTo->day))->map(
            fn (int $day): array => [
                'day' => $day,
                'weekday' => $periodStart->copy()->day($day)->format('D'),
            ],
        )->values();
    }

    private function periodLabel(CarbonInterface $dateFrom, CarbonInterface $dateTo): string
    {
        if ($dateFrom->isSameDay($dateTo)) {
            return $dateFrom->format('j M Y');
        }

        if ($dateFrom->month === $dateTo->month && $dateFrom->year === $dateTo->year) {
            return $dateFrom->format('j').' – '.$dateTo->format('j M Y');
        }

        return $dateFrom->format('j M Y').' – '.$dateTo->format('j M Y');
    }

    /**
     * @param  class-string<Employee|Contractor>  $personnelType
     * @return list<int>
     */
    private function assignedPersonnelIds(
        string $personnelType,
        int $projectId,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
    ): array {
        $periodStart = $dateFrom->copy()->startOfDay();
        $periodEnd = $dateTo->copy()->endOfDay();

        $deploymentIds = ProjectDeployment::query()
            ->where('project_id', $projectId)
            ->where('personnel_type', $personnelType)
            ->where('status', 'active')
            ->where(function ($query) use ($periodEnd) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $periodEnd);
            })
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $periodStart);
            })
            ->pluck('personnel_id');

        return $deploymentIds->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $entry
     */
    private function persistBulkAttendanceEntry(
        Request $request,
        array $attributes,
        string $personnelType,
        array $entry,
        string $action,
    ): string {
        $attendance = null;

        if (! empty($entry['attendance_id'])) {
            $attendance = PersonnelAttendance::query()->find($entry['attendance_id']);

            if (
                $attendance === null
                || $attendance->personnel_type !== $personnelType
                || (int) $attendance->personnel_id !== (int) $entry['personnel_id']
            ) {
                $attendance = null;
            }
        }

        if ($attendance === null) {
            $attendance = $this->findDuplicateAttendance($attributes);
        }

        if ($attendance !== null && ! $this->canEditAttendance($request, $attendance)) {
            return 'locked';
        }

        $payload = [
            'project_id' => $attributes['project_id'],
            'days_present' => $attributes['days_present'],
            'days_absent' => $attributes['days_absent'],
            'days_sick_leave' => $attributes['days_sick_leave'],
            'days_annual_leave' => $attributes['days_annual_leave'],
            'days_casual_leave' => $attributes['days_casual_leave'],
            'days_other' => $attributes['days_other'],
            'days_leave' => $attributes['days_leave'],
            'daily_marks' => $attributes['daily_marks'],
            'overtime_hours' => $attributes['overtime_hours'],
            'status' => $this->resolveStatusAfterAction($action, $attendance?->status),
        ];

        if ($attendance !== null) {
            $attendance->update($payload);

            return 'updated';
        }

        try {
            PersonnelAttendance::query()->create([
                ...$attributes,
                ...$payload,
            ]);

            return 'created';
        } catch (QueryException) {
            return 'skipped';
        }
    }

    private function canEditAttendance(Request $request, PersonnelAttendance $attendance): bool
    {
        if (! $this->isAttendanceLocked($attendance)) {
            return true;
        }

        return $request->user()?->can('hr.edit') === true;
    }

    private function isAttendanceLocked(PersonnelAttendance $attendance): bool
    {
        return in_array($attendance->status, $this->lockedAttendanceStatuses(), true);
    }

    /**
     * @return list<string>
     */
    private function lockedAttendanceStatuses(): array
    {
        return ['submitted', 'approved'];
    }

    private function resolveStatusAfterAction(string $action, ?string $currentStatus): string
    {
        if ($currentStatus === 'approved') {
            return 'approved';
        }

        if ($action === 'submit') {
            return 'submitted';
        }

        if ($currentStatus === null) {
            return 'draft';
        }

        return $currentStatus === 'submitted' ? 'submitted' : 'draft';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    private function activeProjects()
    {
        return Project::query()
            ->where('is_archived', false)
            ->where('status', ProjectStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
