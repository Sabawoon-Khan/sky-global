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
use Inertia\Inertia;
use Inertia\Response;

class PersonnelAttendanceController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $year = $request->integer('year') ?: now()->year;
        $month = $request->integer('month') ?: now()->month;
        $projectId = $request->integer('project_id') ?: null;

        $attendances = PersonnelAttendance::query()
            ->with(['project', 'projectSite'])
            ->where('year', $year)
            ->where('month', $month)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('mis/hr/Attendance/Index', [
            'attendances' => $attendances,
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'code', 'name']),
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'contractors' => Contractor::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'filters' => [
                'year' => $year,
                'month' => $month,
                'project_id' => $projectId,
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

        $validated = $request->validate([
            'personnel_type' => ['required', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'days_present' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_absent' => ['nullable', 'integer', 'min:0', 'max:31'],
            'days_leave' => ['nullable', 'integer', 'min:0', 'max:31'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => ['integer'],
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['personnel_ids'] as $personnelId) {
            $attributes = $this->normalizeAttendanceAttributes([
                'personnel_type' => $validated['personnel_type'],
                'personnel_id' => $personnelId,
                'project_id' => $validated['project_id'] ?? null,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'days_present' => $validated['days_present'] ?? 0,
                'days_absent' => $validated['days_absent'] ?? 0,
                'days_leave' => $validated['days_leave'] ?? 0,
                'overtime_hours' => $validated['overtime_hours'] ?? 0,
            ]);

            if ($this->findDuplicateAttendance($attributes) !== null) {
                $skipped++;

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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$created} attendance records created.".($skipped > 0 ? " {$skipped} skipped (already exist)." : ''),
        ]);

        return redirect()->route('hr.attendance.index', [
            'year' => $validated['year'],
            'month' => $validated['month'],
            'project_id' => $validated['project_id'] ?? null,
        ]);
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

        return redirect()->route('hr.attendance.index', [
            'year' => $attributes['year'],
            'month' => $attributes['month'],
            'project_id' => $attributes['project_id'] ?? null,
        ]);
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
}
