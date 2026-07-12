<?php

namespace App\Services;

use App\Data\NotificationPayload;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyUser(User $user, NotificationPayload $payload): void
    {
        $user->notify(new InAppNotification($payload));
    }

    /**
     * @param  iterable<User>|Collection<int, User>|EloquentCollection<int, User>  $users
     */
    public function notifyUsers(iterable $users, NotificationPayload $payload): void
    {
        $users = $users instanceof Collection ? $users : collect($users);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new InAppNotification($payload));
    }

    /**
     * @param  string|array<int, string>  $roles
     */
    public function notifyRole(string|array $roles, NotificationPayload $payload): void
    {
        $roles = Arr::wrap($roles);
        $users = User::role($roles)->where('is_active', true)->get();

        $this->notifyUsers($users, $this->withAudience($payload, roles: $roles));
    }

    /**
     * @param  string|array<int, string>  $permissions
     */
    public function notifyPermission(string|array $permissions, NotificationPayload $payload): void
    {
        $permissions = Arr::wrap($permissions);
        $users = User::permission($permissions)->where('is_active', true)->get();

        $this->notifyUsers($users, $this->withAudience($payload, permissions: $permissions));
    }

    public function unreadCount(User $user): int
    {
        return $this->visibleNotifications($user, unreadOnly: true)->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recent(User $user, int $limit = 20): Collection
    {
        return $this->visibleNotifications($user)
            ->take($limit)
            ->map(fn (DatabaseNotification $notification) => $this->format($notification));
    }

    public function markAsRead(User $user, string $id): bool
    {
        $notification = $this->visibleNotifications($user)->firstWhere('id', $id);

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return $this->visibleNotifications($user, unreadOnly: true)
            ->each(fn (DatabaseNotification $notification) => $notification->markAsRead())
            ->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    protected function visibleNotifications(User $user, bool $unreadOnly = false): Collection
    {
        $query = $unreadOnly ? $user->unreadNotifications() : $user->notifications();

        return $query
            ->latest()
            ->get()
            ->filter(fn (DatabaseNotification $notification) => $this->isVisibleToUser($notification, $user))
            ->values();
    }

    protected function isVisibleToUser(DatabaseNotification $notification, User $user): bool
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        $roles = $this->stringList($data['roles'] ?? null);
        if ($roles !== [] && ! $user->hasAnyRole($roles)) {
            return false;
        }

        $permissions = $this->stringList($data['permissions'] ?? null);
        if ($permissions !== [] && ! $user->hasAnyPermission($permissions)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function format(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? null,
            'type' => $data['type'] ?? 'info',
            'action_url' => NotificationPayload::normalizeActionUrl(
                is_string($data['action_url'] ?? null) ? $data['action_url'] : null,
            ),
            'action_label' => $data['action_label'] ?? null,
            'module' => $data['module'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'created_at_human' => $notification->created_at->diffForHumans(),
        ];
    }

    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     */
    protected function withAudience(
        NotificationPayload $payload,
        ?array $roles = null,
        ?array $permissions = null,
    ): NotificationPayload {
        return new NotificationPayload(
            title: $payload->title,
            body: $payload->body,
            type: $payload->type,
            actionUrl: $payload->actionUrl,
            actionLabel: $payload->actionLabel,
            module: $payload->module,
            roles: $roles ?? $payload->roles,
            permissions: $permissions ?? $payload->permissions,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? $item : null,
            $value,
        )));
    }
}
