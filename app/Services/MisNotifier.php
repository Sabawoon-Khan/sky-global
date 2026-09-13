<?php

namespace App\Services;

use App\Data\NotificationPayload;
use App\Models\User;

class MisNotifier
{
    /**
     * @var list<string>
     */
    private const WARNING_STATUSES = [
        'archived',
        'blocked',
        'cancelled',
        'closed',
        'declined',
        'inactive',
        'lost',
        'overdue',
        'rejected',
        'suspended',
        'terminated',
    ];

    public function __construct(private NotificationService $notifications) {}

    public function created(string $module, string $record, ?string $url = null, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        $this->notify($module, NotificationPayload::success(
            title: __(':record created', ['record' => $record]),
            body: $this->actorBody(':name created :record.', $actor, $record),
            actionUrl: $url,
            actionLabel: __('View'),
            module: $module,
        ), $actor);
    }

    public function updated(string $module, string $record, ?string $url = null, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        $this->notify($module, NotificationPayload::info(
            title: __(':record updated', ['record' => $record]),
            body: $this->actorBody(':name updated :record.', $actor, $record),
            actionUrl: $url,
            actionLabel: __('View'),
            module: $module,
        ), $actor);
    }

    public function deleted(string $module, string $record, ?string $url = null, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        $this->notify($module, NotificationPayload::warning(
            title: __(':record deleted', ['record' => $record]),
            body: $this->actorBody(':name deleted :record.', $actor, $record),
            actionUrl: $url,
            actionLabel: __('View'),
            module: $module,
        ), $actor);
    }

    public function statusChanged(
        string $module,
        string $record,
        string $status,
        ?string $url = null,
        ?User $actor = null,
    ): void {
        $actor ??= auth()->user();
        $label = ucfirst(str_replace('_', ' ', $status));
        $warning = in_array(strtolower($status), self::WARNING_STATUSES, true);

        $payload = $warning
            ? NotificationPayload::warning(
                title: __(':record status changed', ['record' => $record]),
                body: $this->statusBody($actor, $record, $label),
                actionUrl: $url,
                actionLabel: __('View'),
                module: $module,
            )
            : NotificationPayload::info(
                title: __(':record status changed', ['record' => $record]),
                body: $this->statusBody($actor, $record, $label),
                actionUrl: $url,
                actionLabel: __('View'),
                module: $module,
            );

        $this->notify($module, $payload, $actor);
    }

    public function custom(
        string $module,
        string $title,
        ?string $body = null,
        ?string $url = null,
        string $type = 'info',
        ?User $except = null,
    ): void {
        $payload = new NotificationPayload(
            title: $title,
            body: $body,
            type: $type,
            actionUrl: $url,
            actionLabel: __('View'),
            module: $module,
        );

        $this->notify($module, $payload, $except);
    }

    public function notify(string $module, NotificationPayload $payload, ?User $except = null): void
    {
        $this->notifications->notifyPermission(
            $this->permissionFor($module),
            $payload,
            $except,
        );
    }

    protected function permissionFor(string $module): string
    {
        return match ($module) {
            'organizations' => 'bidding.view',
            'equipment' => 'inventory.view',
            default => "{$module}.view",
        };
    }

    protected function actorBody(string $key, ?User $actor, string $record): ?string
    {
        if ($actor === null) {
            return null;
        }

        return __($key, ['name' => $actor->name, 'record' => $record]);
    }

    protected function statusBody(?User $actor, string $record, string $status): string
    {
        if ($actor === null) {
            return __(':record status changed', ['record' => $record]);
        }

        return __(':name changed :record status to :status.', [
            'name' => $actor->name,
            'record' => $record,
            'status' => $status,
        ]);
    }
}
