import {
    Ban,
    CheckCircle,
    CircleSlash,
    UserCheck,
    UserX,
    XCircle,
} from '@lucide/vue';
import type { RowActionItem } from '@/lib/row-actions';

export function toggleIsActiveAction(options: {
    url: string;
    name: string;
    isActive: boolean;
    entityLabel?: string;
}): RowActionItem {
    const { url, name, isActive, entityLabel = 'record' } = options;

    if (isActive) {
        return {
            label: 'Deactivate',
            icon: Ban,
            separator: true,
            href: url,
            method: 'put',
            data: { is_active: false },
            confirm: {
                title: `Deactivate ${entityLabel}`,
                description: `Deactivate "${name}"? It will no longer appear in active lists.`,
                confirmLabel: 'Deactivate',
            },
            confirmVariant: 'destructive',
        };
    }

    return {
        label: 'Activate',
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
}): RowActionItem[] {
    const { url, name, status } = options;

    if (status === 'active') {
        return [
            {
                label: 'Mark inactive',
                icon: CircleSlash,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'inactive' },
                confirm: {
                    title: 'Mark inactive',
                    description: `Mark "${name}" as inactive?`,
                    confirmLabel: 'Mark inactive',
                },
                confirmVariant: 'default',
            },
        ];
    }

    if (status === 'inactive') {
        return [
            {
                label: 'Mark active',
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
                label: 'Reactivate',
                icon: UserCheck,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'active' },
                confirm: {
                    title: 'Reactivate personnel',
                    description: `Reactivate "${name}"?`,
                    confirmLabel: 'Reactivate',
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
}): RowActionItem[] {
    const { url, name, status } = options;

    if (status === 'pending') {
        return [
            {
                label: 'Approve',
                icon: CheckCircle,
                separator: true,
                href: url,
                method: 'put',
                data: { status: 'approved' },
            },
            {
                label: 'Reject',
                icon: XCircle,
                variant: 'destructive',
                href: url,
                method: 'put',
                data: { status: 'rejected' },
                confirm: {
                    title: 'Reject record',
                    description: `Reject "${name}"?`,
                    confirmLabel: 'Reject',
                },
            },
        ];
    }

    if (status === 'approved') {
        return [
            {
                label: 'Reject',
                icon: XCircle,
                separator: true,
                variant: 'destructive',
                href: url,
                method: 'put',
                data: { status: 'rejected' },
                confirm: {
                    title: 'Reject record',
                    description: `Reject "${name}"?`,
                    confirmLabel: 'Reject',
                },
            },
        ];
    }

    if (status === 'rejected') {
        return [
            {
                label: 'Approve',
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
}): RowActionItem[] {
    const { url, label, status } = options;
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
            label: transition.label,
            separator: index === 0,
            href: url,
            method: 'put',
            data: { status: transition.status },
        });
    }

    if (['draft', 'sent', 'overdue'].includes(status)) {
        actions.push({
            label: 'Cancel invoice',
            icon: XCircle,
            variant: 'destructive',
            href: url,
            method: 'put',
            data: { status: 'cancelled' },
            confirm: {
                title: 'Cancel invoice',
                description: `Cancel ${label}?`,
                confirmLabel: 'Cancel invoice',
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
): RowActionItem[] {
    const transitions = PROJECT_TRANSITIONS[currentStatus] ?? [];

    return transitions.map((transition, index) => ({
        label: transition.label,
        separator: index === 0,
        href: `/projects/${projectId}/status`,
        method: 'post',
        data: { status: transition.value },
        variant: transition.destructive ? 'destructive' : 'default',
        confirm: transition.destructive
            ? {
                  title: transition.label,
                  description: `Change project status to "${transition.value}"?`,
                  confirmLabel: transition.label,
              }
            : undefined,
        confirmVariant: transition.destructive ? 'destructive' : 'default',
    }));
}

export function attendanceStatusActions(
    recordId: number,
    status: string,
): RowActionItem[] {
    if (status === 'approved') {
        return [];
    }

    return [
        {
            label: 'Approve',
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
