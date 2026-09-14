<?php

namespace App\Http\Controllers;

use App\Data\NotificationPayload;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Models\Assignment;
use App\Models\AssignmentRecipient;
use App\Models\AssignmentReply;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): Response
    {
        $this->authorizeAssignmentsModule($request);

        $assignments = $this->visibleAssignmentsQuery($request)
            ->with([
                'createdBy:id,name',
                'recipients.user:id,name',
                'attachments',
            ])
            ->withCount('recipients', 'replies')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $canAssign = $request->user()->can('assignments.create');

        return Inertia::render('mis/assignments/Index', [
            'assignments' => $assignments,
            'users' => $canAssign
                ? User::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                : [],
            'canAssign' => $canAssign,
        ]);
    }

    public function show(Request $request, Assignment $assignment): Response
    {
        $this->authorizeAssignmentsModule($request);
        $this->authorizeAssignmentAccess($request, $assignment);

        $assignment->load([
            'createdBy:id,name',
            'attachments',
            'recipients.user:id,name',
            'replies.user:id,name',
            'replies.attachments',
        ]);

        $myRecipient = $assignment->recipients
            ->firstWhere('user_id', $request->user()->id);

        return Inertia::render('mis/assignments/Show', [
            'assignment' => $assignment,
            'canManage' => $this->canManageAssignments($request),
            'isRecipient' => $myRecipient !== null,
            'myRecipientStatus' => $myRecipient?->status,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'assignments.create');

        $userIds = $request->input('user_ids');
        if (! is_array($userIds)) {
            $request->merge([
                'user_ids' => $userIds !== null && $userIds !== '' ? [$userIds] : [],
            ]);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:10000'],
            'assigned_on' => ['nullable', 'date'],
            'reply_by' => [
                'nullable',
                'date',
                Rule::when(
                    filled($request->input('assigned_on')),
                    'after_or_equal:assigned_on',
                ),
            ],
            'status' => ['nullable', Rule::in([
                Assignment::STATUS_PENDING,
                Assignment::STATUS_COMPLETED,
                Assignment::STATUS_CANCELLED,
            ])],
            'assign_to' => ['required', Rule::in(['users', 'all'])],
            'user_ids' => ['required_if:assign_to,users', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $recipientIds = $this->resolveRecipientIds($validated);

        if ($recipientIds === []) {
            throw ValidationException::withMessages([
                'user_ids' => [__('Select at least one user.')],
            ]);
        }

        $assignment = Assignment::query()->create([
            'description' => $validated['description'],
            'assigned_on' => $validated['assigned_on'] ?? null,
            'reply_by' => $validated['reply_by'] ?? null,
            'status' => $validated['status'] ?? Assignment::STATUS_PENDING,
            'created_by' => $request->user()->id,
        ]);

        foreach ($recipientIds as $userId) {
            $assignment->recipients()->create([
                'user_id' => $userId,
                'status' => AssignmentRecipient::STATUS_PENDING,
            ]);
        }

        $this->storeOptionalAttachment($request, $assignment);

        $this->notifyAssignees($assignment, $recipientIds, $request->user());

        return redirect()
            ->route('assignments.show', $assignment)
            ->with('success', __('Assignment created.'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizePermission($request, 'assignments.edit');
        $this->authorizeAssignmentAccess($request, $assignment);

        $validated = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'assigned_on' => ['sometimes', 'nullable', 'date'],
            'reply_by' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in([
                Assignment::STATUS_PENDING,
                Assignment::STATUS_COMPLETED,
                Assignment::STATUS_CANCELLED,
            ])],
            'recipient_statuses' => ['sometimes', 'array'],
            'recipient_statuses.*.id' => ['required', 'integer', 'exists:assignment_recipients,id'],
            'recipient_statuses.*.status' => ['required', Rule::in([
                AssignmentRecipient::STATUS_PENDING,
                AssignmentRecipient::STATUS_SUBMITTED,
                AssignmentRecipient::STATUS_COMPLETED,
            ])],
        ]);

        $assignment->update(collect($validated)->except('recipient_statuses')->all());

        if (isset($validated['recipient_statuses'])) {
            foreach ($validated['recipient_statuses'] as $row) {
                AssignmentRecipient::query()
                    ->where('assignment_id', $assignment->id)
                    ->whereKey($row['id'])
                    ->update(['status' => $row['status']]);
            }
        }

        return back()->with('success', __('Assignment updated.'));
    }

    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizePermission($request, 'assignments.delete');

        $assignment->delete();

        return redirect()
            ->route('assignments.index')
            ->with('success', __('Assignment deleted.'));
    }

    public function storeReply(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeAssignmentsModule($request);
        $this->authorizeAssignmentAccess($request, $assignment);

        $recipient = AssignmentRecipient::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($recipient !== null, 403);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:10000'],
        ]);

        $reply = $assignment->replies()->create([
            'user_id' => $request->user()->id,
            'description' => $validated['description'],
        ]);

        $this->storeOptionalAttachment($request, $reply);

        if ($recipient->status === AssignmentRecipient::STATUS_PENDING) {
            $recipient->update(['status' => AssignmentRecipient::STATUS_SUBMITTED]);
        }

        $creator = $assignment->createdBy;

        if ($creator !== null && (int) $creator->id !== (int) $request->user()->id) {
            $this->notifications->notifyUser(
                $creator,
                NotificationPayload::info(
                    title: __('New reply on assignment'),
                    body: Str::limit($validated['description'], 120),
                    actionUrl: route('assignments.show', $assignment, false),
                    actionLabel: __('View'),
                    module: 'assignments',
                ),
            );
        }

        return back()->with('success', __('Reply submitted.'));
    }

    private function canManageAssignments(Request $request): bool
    {
        return $request->user()->can('assignments.create')
            || $request->user()->can('assignments.edit')
            || $request->user()->can('assignments.delete');
    }

    private function canViewAllAssignments(Request $request): bool
    {
        return $request->user()->can('assignments.view_all')
            || $request->user()->can('assignments.create');
    }

    private function authorizeAssignmentsModule(Request $request): void
    {
        $user = $request->user();

        if ($user->can('assignments.view') || $this->canViewAllAssignments($request)) {
            return;
        }

        abort_unless($this->userIsAssignee($user->id), 403);
    }

    private function userIsAssignee(int $userId): bool
    {
        return AssignmentRecipient::query()
            ->where('user_id', $userId)
            ->exists();
    }

    private function visibleAssignmentsQuery(Request $request)
    {
        $userId = (int) $request->user()->id;
        $query = Assignment::query();

        if ($this->canViewAllAssignments($request)) {
            return $query;
        }

        return $query->whereIn(
            'id',
            AssignmentRecipient::query()
                ->where('user_id', $userId)
                ->select('assignment_id'),
        );
    }

    private function authorizeAssignmentAccess(Request $request, Assignment $assignment): void
    {
        if ($this->canViewAllAssignments($request)) {
            return;
        }

        $isRecipient = AssignmentRecipient::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', (int) $request->user()->id)
            ->exists();

        abort_unless($isRecipient, 403);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function resolveRecipientIds(array $validated): array
    {
        if ($validated['assign_to'] === 'all') {
            return User::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return collect($validated['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $recipientIds
     */
    private function notifyAssignees(Assignment $assignment, array $recipientIds, User $actor): void
    {
        if ($recipientIds === []) {
            return;
        }

        $users = User::query()->whereIn('id', $recipientIds)->get();

        $this->notifications->notifyUsers(
            $users,
            NotificationPayload::info(
                title: __('New assignment'),
                body: Str::limit($assignment->description, 120),
                actionUrl: route('assignments.show', $assignment, false),
                actionLabel: __('View'),
                module: 'assignments',
            ),
            $actor,
        );
    }
}
