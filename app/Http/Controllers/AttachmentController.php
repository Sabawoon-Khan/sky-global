<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\ServesStoredFiles;
use App\Models\Attachment;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Finance\TaxPayment;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Assignment;
use App\Models\AssignmentReply;
use App\Models\Organization;
use App\Models\Procurement\CompetitorBid;
use App\Models\Project\Project;
use App\Models\Project\ProjectIssue;
use App\Models\StatusChangeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesMisPermissions, ServesStoredFiles;

    /** @var array<class-string, string> */
    private const PERMISSION_MAP = [
        Organization::class => 'bidding.view',
        Project::class => 'projects.view',
        Employee::class => 'hr.view',
        StatusChangeLog::class => 'hr.view',
        Contractor::class => 'hr.view',
        CompetitorBid::class => 'bidding.view_competitors',
        ProjectIncome::class => 'finance.view',
        ProjectExpense::class => 'finance.view',
        GeneralExpense::class => 'finance.view',
        GeneralIncome::class => 'finance.view',
        Invoice::class => 'finance.view',
        TaxPayment::class => 'finance.view',
        PersonnelAttendance::class => 'hr.view',
        PayrollRun::class => 'hr.view',
        ProjectIssue::class => 'projects.view',
        Assignment::class => 'assignments.view',
        AssignmentReply::class => 'assignments.view',
        EquipmentCatalog::class => 'hr.view',
    ];

    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);

        return $this->serveLocalFile(
            $request,
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
        if ($attachment->attachable_type === Assignment::class) {
            $this->authorizeAssignmentAttachment($request, $attachment->attachable);

            return;
        }

        if ($attachment->attachable_type === AssignmentReply::class) {
            $reply = $attachment->attachable;

            if ($reply instanceof AssignmentReply) {
                $this->authorizeAssignmentAttachment($request, $reply->assignment);
            }

            return;
        }

        $permission = self::PERMISSION_MAP[$attachment->attachable_type] ?? null;

        if ($permission) {
            $this->authorizePermission($request, $permission);
        }
    }

    private function authorizeAssignmentAttachment(Request $request, mixed $assignment): void
    {
        if (! $assignment instanceof Assignment) {
            abort(404);
        }

        $user = $request->user();

        if (
            $user->can('assignments.view')
            || $user->can('assignments.view_all')
            || $user->can('assignments.create')
        ) {
            return;
        }

        $isRecipient = $assignment->recipients()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isRecipient, 403);
    }
}
