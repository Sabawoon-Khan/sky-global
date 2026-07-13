import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { requestJson } from '@/lib/http';
import { resolveNotificationPath } from '@/lib/notifications';
import type { AppNotification } from '@/types/notifications';

type NotificationIndexResponse = {
    notifications: AppNotification[];
    unread_count: number;
};

function sharedUnreadCount(page: ReturnType<typeof usePage>): number {
    const shared = page.props.notifications as { unread_count?: number } | null;

    return shared?.unread_count ?? 0;
}

export function useNotifications() {
    const page = usePage();
    const items = ref<AppNotification[]>([]);
    const loading = ref(false);
    const open = ref(false);
    const unreadCount = ref(sharedUnreadCount(page));

    watch(
        () => page.props.notifications,
        (shared) => {
            if (shared && typeof shared === 'object' && 'unread_count' in shared) {
                unreadCount.value = (shared as { unread_count: number }).unread_count;
            }
        },
    );

    const hasUnread = computed(() => unreadCount.value > 0);

    async function fetchNotifications(): Promise<void> {
        loading.value = true;

        try {
            const data = await requestJson<NotificationIndexResponse>('/notifications');
            items.value = data.notifications;
            unreadCount.value = data.unread_count;
        } finally {
            loading.value = false;
        }
    }

    async function openPanel(): Promise<void> {
        open.value = true;
        await fetchNotifications();
    }

    function closePanel(): void {
        open.value = false;
    }

    async function markAsRead(id: string): Promise<void> {
        const data = await requestJson<{ unread_count: number }>(
            `/notifications/${id}/read`,
            { method: 'POST' },
        );

        items.value = items.value.map((item) =>
            item.id === id ? { ...item, read_at: new Date().toISOString() } : item,
        );
        unreadCount.value = data.unread_count;
    }

    async function markAllAsRead(): Promise<void> {
        await requestJson<{ unread_count: number }>('/notifications/read-all', {
            method: 'POST',
        });

        items.value = items.value.map((item) => ({
            ...item,
            read_at: item.read_at ?? new Date().toISOString(),
        }));
        unreadCount.value = 0;
    }

    async function handleNotificationClick(
        notification: AppNotification,
    ): Promise<void> {
        if (!notification.read_at) {
            try {
                await markAsRead(notification.id);
            } catch {
                // Navigation should still work if marking read fails.
            }
        }

        const path = resolveNotificationPath(notification.action_url);

        if (!path) {
            return;
        }

        closePanel();
        router.visit(path);
    }

    return {
        items,
        loading,
        open,
        unreadCount,
        hasUnread,
        openPanel,
        closePanel,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        handleNotificationClick,
    };
}
