import { Eye, Pencil, Trash2 } from '@lucide/vue';
import type { RowActionItem } from '@/lib/row-actions';
import { useTranslations } from '@/composables/useTranslations';

export type MisTranslator = ReturnType<typeof useTranslations>['t'];

export function useMisPage() {
    const { t } = useTranslations();

    const viewAction = (href: string): RowActionItem => ({
        label: t('View'),
        icon: Eye,
        href,
    });

    const editAction = (href: string): RowActionItem => ({
        label: t('Edit'),
        icon: Pencil,
        href,
    });

    const deleteAction = (options: {
        href: string;
        title: string;
        description: string;
        method?: 'delete' | 'post' | 'put';
    }): RowActionItem => ({
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: options.href,
        method: options.method ?? 'delete',
        confirm: {
            title: options.title,
            description: options.description,
            confirmLabel: t('Delete'),
        },
    });

    return {
        t,
        viewAction,
        editAction,
        deleteAction,
    };
}
