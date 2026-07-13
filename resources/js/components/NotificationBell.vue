<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    Bell,
    CheckCircle2,
    Info,
    LoaderCircle,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/composables/useNotifications';
import { useTranslations } from '@/composables/useTranslations';
import { resolveNotificationPath } from '@/lib/notifications';
import type { AppNotification, NotificationType } from '@/types/notifications';

const typeStyles: Record<
    NotificationType,
    { icon: typeof Info; iconClass: string }
> = {
    info: {
        icon: Info,
        iconClass: 'text-blue-600 bg-blue-50 dark:bg-blue-950/50 dark:text-blue-400',
    },
    success: {
        icon: CheckCircle2,
        iconClass: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-400',
    },
    warning: {
        icon: AlertTriangle,
        iconClass: 'text-amber-600 bg-amber-50 dark:bg-amber-950/50 dark:text-amber-400',
    },
    error: {
        icon: AlertCircle,
        iconClass: 'text-red-600 bg-red-50 dark:bg-red-950/50 dark:text-red-400',
    },
};

const {
    items,
    loading,
    open,
    unreadCount,
    hasUnread,
    openPanel,
    closePanel,
    markAllAsRead,
    markAsRead,
    handleNotificationClick,
} = useNotifications();

const { t } = useTranslations();

const itemClass =
    'flex w-full cursor-pointer gap-3 rounded-none px-4 py-3 text-start transition-colors hover:bg-muted/40 focus:bg-muted/40';

function onOpenChange(isOpen: boolean): void {
    if (isOpen) {
        void openPanel();
    } else {
        closePanel();
    }
}

function notificationIcon(type: NotificationType) {
    return typeStyles[type]?.icon ?? Info;
}

function notificationIconClass(type: NotificationType) {
    return typeStyles[type]?.iconClass ?? typeStyles.info.iconClass;
}

function onNotificationNavigate(notification: AppNotification): void {
    if (!notification.read_at) {
        void markAsRead(notification.id);
    }

    closePanel();
}

function onNotificationSelect(notification: AppNotification): void {
    void handleNotificationClick(notification);
}
</script>

<template>
    <DropdownMenu :open="open" @update:open="onOpenChange">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-9 rounded-full text-muted-foreground hover:text-foreground"
            >
                <Bell class="size-[18px]" stroke-width="1.75" />
                <span
                    v-if="hasUnread"
                    class="absolute end-2 top-2 flex size-4 min-w-4 items-center justify-center rounded-full bg-school-gold px-0.5 text-[10px] font-bold leading-none text-white ring-2 ring-background"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
                <span class="sr-only">{{ t('Notifications') }}</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            :side-offset="8"
            class="w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border-border/60 p-0 shadow-[0_20px_60px_-20px_rgba(15,23,42,0.25)]"
            @close-auto-focus.prevent
        >
            <div
                class="flex items-center justify-between border-b border-border/60 bg-muted/30 px-4 py-3"
            >
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        {{ t('Notifications') }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            hasUnread
                                ? t(':count unread', { count: String(unreadCount) })
                                : t('You are all caught up')
                        }}
                    </p>
                </div>
                <Button
                    v-if="hasUnread"
                    variant="ghost"
                    size="sm"
                    class="h-8 rounded-full px-3 text-xs"
                    @click.stop="markAllAsRead"
                >
                    {{ t('Mark all read') }}
                </Button>
            </div>

            <div class="max-h-[min(24rem,60vh)] overflow-y-auto">
                <div
                    v-if="loading && items.length === 0"
                    class="flex items-center justify-center gap-2 px-4 py-10 text-sm text-muted-foreground"
                >
                    <LoaderCircle class="size-4 animate-spin" />
                    {{ t('Loading notifications...') }}
                </div>

                <div
                    v-else-if="items.length === 0"
                    class="flex flex-col items-center gap-2 px-4 py-10 text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Bell class="size-5" stroke-width="1.75" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        {{ t('No notifications yet') }}
                    </p>
                    <p class="max-w-[16rem] text-xs text-muted-foreground">
                        {{ t('When something needs your attention, it will show up here.') }}
                    </p>
                </div>

                <ul v-else class="divide-y divide-border/60">
                    <li v-for="notification in items" :key="notification.id">
                        <DropdownMenuItem
                            v-if="resolveNotificationPath(notification.action_url)"
                            as-child
                            class="p-0 focus:bg-transparent"
                            @select.prevent
                        >
                            <Link
                                :href="resolveNotificationPath(notification.action_url)!"
                                :class="[
                                    itemClass,
                                    { 'bg-school-gold/5': !notification.read_at },
                                ]"
                                @click="onNotificationNavigate(notification)"
                            >
                                <div
                                    class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl"
                                    :class="notificationIconClass(notification.type)"
                                >
                                    <component
                                        :is="notificationIcon(notification.type)"
                                        class="size-4"
                                        stroke-width="2"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start gap-2">
                                        <p
                                            class="line-clamp-2 text-sm leading-snug"
                                            :class="
                                                notification.read_at
                                                    ? 'font-medium text-muted-foreground'
                                                    : 'font-semibold text-foreground'
                                            "
                                        >
                                            {{ notification.title }}
                                        </p>
                                        <span
                                            v-if="!notification.read_at"
                                            class="mt-1.5 size-2 shrink-0 rounded-full bg-school-gold"
                                        />
                                    </div>

                                    <p
                                        v-if="notification.body"
                                        class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ notification.body }}
                                    </p>

                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-[11px] text-muted-foreground">
                                            {{ notification.created_at_human }}
                                        </span>
                                        <span class="text-[11px] font-medium text-primary">
                                            {{ notification.action_label ?? t('View') }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </DropdownMenuItem>

                        <DropdownMenuItem
                            v-else
                            :class="[
                                itemClass,
                                { 'bg-school-gold/5': !notification.read_at },
                            ]"
                            @select="onNotificationSelect(notification)"
                        >
                            <div
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl"
                                :class="notificationIconClass(notification.type)"
                            >
                                <component
                                    :is="notificationIcon(notification.type)"
                                    class="size-4"
                                    stroke-width="2"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <p
                                        class="line-clamp-2 text-sm leading-snug"
                                        :class="
                                            notification.read_at
                                                ? 'font-medium text-muted-foreground'
                                                : 'font-semibold text-foreground'
                                        "
                                    >
                                        {{ notification.title }}
                                    </p>
                                    <span
                                        v-if="!notification.read_at"
                                        class="mt-1.5 size-2 shrink-0 rounded-full bg-school-gold"
                                    />
                                </div>

                                <p
                                    v-if="notification.body"
                                    class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ notification.body }}
                                </p>

                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-[11px] text-muted-foreground">
                                        {{ notification.created_at_human }}
                                    </span>
                                </div>
                            </div>
                        </DropdownMenuItem>
                    </li>
                </ul>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
