<?php

namespace App\Data;

class NotificationPayload
{
    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     */
    public function __construct(
        public string $title,
        public ?string $body = null,
        public string $type = 'info',
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $module = null,
        public ?array $roles = null,
        public ?array $permissions = null,
    ) {
        $this->actionUrl = self::normalizeActionUrl($actionUrl);
    }

    public static function info(
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $module = null,
    ): self {
        return new self(
            title: $title,
            body: $body,
            type: 'info',
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            module: $module,
        );
    }

    public static function success(
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $module = null,
    ): self {
        return new self(
            title: $title,
            body: $body,
            type: 'success',
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            module: $module,
        );
    }

    public static function warning(
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $module = null,
    ): self {
        return new self(
            title: $title,
            body: $body,
            type: 'warning',
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            module: $module,
        );
    }

    public static function error(
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $module = null,
    ): self {
        return new self(
            title: $title,
            body: $body,
            type: 'error',
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            module: $module,
        );
    }

    public static function normalizeActionUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parsed = parse_url($url);

        if (! is_array($parsed) || ! isset($parsed['path'])) {
            return $url;
        }

        $path = $parsed['path'];

        if (isset($parsed['query']) && $parsed['query'] !== '') {
            $path .= '?'.$parsed['query'];
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'module' => $this->module,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
