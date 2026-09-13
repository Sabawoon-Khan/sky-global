<?php

namespace App\Http\Controllers\Concerns;

use App\Data\NotificationPayload;
use App\Services\MisNotifier;

trait NotifiesMisUsers
{
    protected function notifyMisCreated(string $module, string $record, ?string $url = null): void
    {
        app(MisNotifier::class)->created($module, $record, $url);
    }

    protected function notifyMisUpdated(string $module, string $record, ?string $url = null): void
    {
        app(MisNotifier::class)->updated($module, $record, $url);
    }

    protected function notifyMisDeleted(string $module, string $record, ?string $url = null): void
    {
        app(MisNotifier::class)->deleted($module, $record, $url);
    }

    protected function notifyMisStatus(string $module, string $record, string $status, ?string $url = null): void
    {
        app(MisNotifier::class)->statusChanged($module, $record, $status, $url);
    }

    protected function notifyMisCustom(
        string $module,
        string $title,
        ?string $body = null,
        ?string $url = null,
        string $type = 'info',
    ): void {
        app(MisNotifier::class)->custom($module, $title, $body, $url, $type);
    }

    protected function notifyMis(string $module, NotificationPayload $payload): void
    {
        app(MisNotifier::class)->notify($module, $payload, auth()->user());
    }
}
