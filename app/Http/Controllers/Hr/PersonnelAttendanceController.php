<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Project\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $attendance = PersonnelAttendance::query()->create([
            ...$validated,
            'status' => 'draft',
        ]);
        $this->storeOptionalAttachment($request, $attendance);

        return back()->with('success', 'Attendance record created.');
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

        $attendance->update($validated);

        return back()->with('success', 'Attendance record updated.');
    }

    public function approve(Request $request, PersonnelAttendance $attendance): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $attendance->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Attendance approved.');
    }
}
