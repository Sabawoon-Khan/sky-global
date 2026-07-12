<?php

namespace App\Notifications;

use App\Data\NotificationPayload;
use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    public function __construct(public NotificationPayload $payload) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload->toArray();
    }
}
