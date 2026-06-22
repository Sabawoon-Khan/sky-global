import {
    Ban,
    CheckCircle,
    CircleSlash,
    UserCheck,
    UserX,
    XCircle,
} from '@lucide/vue';
import type { RowActionItem } from '@/lib/row-actions';
import type { MisTranslator } from '@/composables/useMisPage';

type Translate = MisTranslator;

function tr(t: Translate | undefined, key: string, replace?: Record<string, string>): string {
    return t ? t(key, replace) : key;
}

export function toggleIsActiveAction(options: {
    url: string;
    name: string;
    isActive: boolean;
    entityLabel?: string;
    t?: Translate;
}): RowActionItem {
    const { url, name, isActive, entityLabel = 'record', t } = options;

    if (isActive) {
        return {
            label: tr(t, 'Deactivate'),
            icon: Ban,
            separator: true,
            href: url,
            method: 'put',
            data: { is_active: false },
            confirm: {
                title: tr(t, 'Deactivate :entity', { entity: entityLabel }),
                description: tr(t, 'Deactivate ":name"? It will no longer appear in active lists.', {
                    name,
                }),
                confirmLabel: tr(t, 'Deactivate'),
            },
            confirmVariant: 'destructive',
        };
    }

    return {
        label: tr(t, 'Activate'),
        icon: CheckCircle,
        separator: true,
        href: url,
        method: 'put',
        data: { is_active: true },
    };
}

export function personnelStatusActions(options: {
    url: string;
    name: string;
    status: string;
    t?: Translate;
}): RowActionItem[] {
    const { url, name, status, t } = options;

    if (status === 'active') {
        return [
            {
                label: tr(t, 'Mark inactive'),
                icon: CircleSlash,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'inactive' },
                confirm: {
                    title: tr(t, 'Mark inactive'),
                    description: tr(t, 'Mark ":name" as inactive?', { name }),
                    confirmLabel: tr(t, 'Mark inactive'),
                },
                confirmVariant: 'default',
            },
        ];
    }

    if (status === 'inactive') {
        return [
            {
                label: tr(t, 'Mark active'),
                icon: UserCheck,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'active' },
            },
        ];
    }

    if (status === 'terminated') {
        return [
            {
                label: tr(t, 'Reactivate'),
                icon: UserCheck,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'active' },
                confirm: {
                    title: tr(t, 'Reactivate personnel'),
                    description: tr(t, 'Reactivate ":name"?', { name }),
                    confirmLabel: tr(t, 'Reactivate'),
                },
                confirmVariant: 'default',
            },
        ];
    }

    return [];
}

export function approvalStatusActions(options: {
    url: string;
    name: string;
    status: string;
    t?: Translate;
}): RowActionItem[] {
    const { url, name, status, t } = options;

    if (status === 'pending') {
        return [
            {
                label: tr(t, 'Approve'),
                icon: CheckCircle,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'approved' },
            },
            {
                label: tr(t, 'Reject'),
                icon: XCircle,
                variant: 'destructive',
                href: url,
                method: 'put',
                data: { status: 'rejected' },
                confirm: {
                    title: tr(t, 'Reject record'),
                    description: tr(t, 'Reject ":name"?', { name }),
                    confirmLabel: tr(t, 'Reject'),
                },
            },
        ];
    }

    if (status === 'approved') {
        return [
            {
                label: tr(t, 'Reject'),
                icon: XCircle,
                separator: true,
                variant: 'destructive',
                href: url,
                method: 'put',
                data: { status: 'rejected' },
                confirm: {
                    title: tr(t, 'Reject record'),
                    description: tr(t, 'Reject ":name"?', { name }),
                    confirmLabel: tr(t, 'Reject'),
                },
            },
        ];
    }

    if (status === 'rejected') {
        return [
            {
                label: tr(t, 'Approve'),
                icon: CheckCircle,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'approved' },
            },
        ];
    }

    return [];
}

export function invoiceStatusActions(options: {
    url: string;
    label: string;
    status: string;
    t?: Translate;
}): RowActionItem[] {
    const { url, label, status, t } = options;
    const actions: RowActionItem[] = [];

    const transitions: Record<string, Array<{ status: string; label: string }>> = {
        draft: [{ status: 'sent', label: 'Mark as sent' }],
        sent: [
            { status: 'paid', label: 'Mark as paid' },
            { status: 'overdue', label: 'Mark overdue' },
        ],
        overdue: [{ status: 'paid', label: 'Mark as paid' }],
    };

    for (const [index, transition] of (transitions[status] ?? []).entries()) {
        actions.push({
            label: tr(t, transition.label),
            separator: index === 0,
            href: url,
            method: 'put',
            data: { status: transition.status },
        });
    }

    if (['draft', 'sent', 'overdue'].includes(status)) {
        actions.push({
            label: tr(t, 'Cancel invoice'),
            icon: XCircle,
            variant: 'destructive',
            href: url,
            method: 'put',
            data: { status: 'cancelled' },
            confirm: {
                title: tr(t, 'Cancel invoice'),
                description: tr(t, 'Cancel :label?', { label }),
                confirmLabel: tr(t, 'Cancel invoice'),
            },
        });
    }

    return actions;
}

const PROJECT_TRANSITIONS: Record<
    string,
    Array<{ value: string; label: string; destructive?: boolean }>
> = {
    draft: [
        { value: 'submitted', label: 'Mark submitted' },
        { value: 'lost', label: 'Mark lost', destructive: true },
    ],
    submitted: [
        { value: 'won', label: 'Mark won' },
        { value: 'lost', label: 'Mark lost', destructive: true },
        { value: 'draft', label: 'Revert to draft' },
    ],
    won: [
        { value: 'active', label: 'Mark active' },
        { value: 'completed', label: 'Mark completed' },
    ],
    lost: [{ value: 'draft', label: 'Revert to draft' }],
    active: [
        { value: 'completed', label: 'Mark completed' },
        { value: 'suspended', label: 'Suspend' },
    ],
    suspended: [
        { value: 'active', label: 'Resume' },
        { value: 'closed', label: 'Close project' },
    ],
    completed: [{ value: 'closed', label: 'Close project' }],
};

export function projectStatusActions(
    projectId: number,
    currentStatus: string,
    t?: Translate,
): RowActionItem[] {
    const transitions = PROJECT_TRANSITIONS[currentStatus] ?? [];

    return transitions.map((transition, index) => ({
        label: tr(t, transition.label),
        separator: index === 0,
        href: `/projects/${projectId}/status`,
        method: 'post',
        data: { status: transition.value },
        variant: transition.destructive ? 'destructive' : 'default',
        confirm: transition.destructive
            ? {
                  title: tr(t, transition.label),
                  description: tr(t, 'Change project status to ":status"?', {
                      status: transition.value,
                  }),
                  confirmLabel: tr(t, transition.label),
              }
            : undefined,
        confirmVariant: transition.destructive ? 'destructive' : 'default',
    }));
}

export function attendanceStatusActions(
    recordId: number,
    status: string,
    t?: Translate,
): RowActionItem[] {
    if (status === 'approved') {
        return [];
    }

    return [
        {
            label: tr(t, 'Approve'),
            icon: CheckCircle,
            separator: true,
            href: `/hr/attendance/${recordId}/approve`,
            method: 'post',
        },
    ];
}

export function userActiveAction(options: {
    url: string;
    name: string;
    isActive: boolean;
    isCurrentUser?: boolean;
}): RowActionItem | null {
    if (options.isCurrentUser && options.isActive) {
        return null;
    }

    return {
        label: options.isActive ? 'Disable account' : 'Enable account',
        icon: options.isActive ? UserX : UserCheck,
        separator: true,
        href: options.url,
        method: 'put',
        data: { is_active: !options.isActive },
        confirm: options.isActive
            ? {
                  title: 'Disable account',
                  description: `Disable "${options.name}"? They will lose access.`,
                  confirmLabel: 'Disable',
              }
            : undefined,
        confirmVariant: 'destructive',
    };
}
