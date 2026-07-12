export type NotificationType = 'info' | 'success' | 'warning' | 'error';

export type AppNotification = {
    id: string;
    title: string;
    body: string | null;
    type: NotificationType;
    action_url: string | null;
    action_label: string | null;
    module: string | null;
    read_at: string | null;
    created_at: string;
    created_at_human: string;
};

export type NotificationsPageProps = {
    unread_count: number;
};
