<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Models\Attachment;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Organization;
use App\Models\Procurement\CompetitorBid;
use App\Models\Project\Project;
use App\Models\Project\ProjectIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesMisPermissions;

    /** @var array<class-string, string> */
    private const PERMISSION_MAP = [
        Organization::class => 'bidding.view',
        Project::class => 'projects.view',
        Employee::class => 'hr.view',
        Contractor::class => 'hr.view',
        CompetitorBid::class => 'bidding.view_competitors',
        ProjectIncome::class => 'finance.view',
        ProjectExpense::class => 'finance.view',
        GeneralExpense::class => 'finance.view',
        Invoice::class => 'finance.view',
        PersonnelAttendance::class => 'hr.view',
        PayrollRun::class => 'hr.view',
        ProjectIssue::class => 'projects.view',
        EquipmentCatalog::class => 'hr.view',
    ];

    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_filename,
        );
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        $this->authorizeAttachment($request, $attachment);

        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    private function authorizeAttachment(Request $request, Attachment $attachment): void
    {
        $permission = self::PERMISSION_MAP[$attachment->attachable_type] ?? null;

        if ($permission) {
            $this->authorizePermission($request, $permission);
        }
    }
}
