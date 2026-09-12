<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { approvalStatusActions } from '@/lib/status-actions';
import { ArrowDownRight, Paperclip } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface Expense {
    id: number;
    description: string;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    transaction_date?: string | null;
    status?: string | null;
    project?: { id: number; code: string; name: string } | null;
    attachments?: FinanceAttachment[];
}

const props = defineProps<{
    expenses: Paginated<Expense>;
    filters?: { project_id?: number | null };
    stats?: { total?: number; count?: number };
}>();

const { t, editAction, deleteAction, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Project Expenses', href: '/finance/expenses' },
        ],
    },
});

const money = (value?: number | null): string => formatAfn(value);

const expenseActions = (item: Expense): RowActionItem[] => [
    ...(item.project
        ? [editAction(`/projects/${item.project.id}`, 'projects.view')]
        : []),
    ...gateActions(
        approvalStatusActions({
            url: `/finance/expenses/${item.id}`,
            name: item.description,
            status: item.status ?? 'pending',
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/expenses/${item.id}`,
            title: t('Delete expense record'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description },
            ),
        },
        'finance.delete',
    ),
];
</script>

<template>
    <Head :title="t('Project Expenses')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Project Expenses') }}</template>
            <template #description>
                {{ t('Expenses recorded against projects.') }}
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        icon-tone="warm"
                        :title="t('Records')"
                        :value="String(stats?.count ?? expenses.total ?? 0)"
                    >
                        <template #icon><ArrowDownRight /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="orange"
                        :title="t('Total')"
                        :value="formatAfn(stats?.total)"
                    >
                        <template #icon><ArrowDownRight /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="text-base">{{
                    t('Project Expenses')
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!props.expenses.data.length"
                    class="ui-empty-state"
                >
                    {{ t('No expense records found.') }}
                </div>
                <div v-else class="space-y-0">
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b bg-muted/40 text-start text-muted-foreground"
                            >
                                <tr>
                                    <th class="px-3 py-2 font-medium">
                                        {{ t('Description') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ t('Project') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ t('Date') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ t('Status') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ t('Attachment') }}
                                    </th>
                                    <th class="px-3 py-2 text-end font-medium">
                                        {{ t('Amount') }}
                                    </th>
                                    <th class="px-3 py-2 text-end font-medium">
                                        {{ t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="item in props.expenses.data"
                                    :key="item.id"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2">
                                        {{ item.description }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">
                                        {{ item.project?.code ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">
                                        {{ formatDate(item.transaction_date) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <Badge variant="outline">
                                            {{ item.status ?? t('pending') }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-2">
                                        <a
                                            v-if="item.attachments?.length"
                                            :href="
                                                item.attachments[0]
                                                    .download_url
                                            "
                                            class="inline-flex items-center gap-1 text-primary hover:underline"
                                            :title="
                                                item.attachments[0]
                                                    .original_filename
                                            "
                                        >
                                            <Paperclip
                                                class="size-3.5 shrink-0"
                                            />
                                            <span
                                                class="max-w-[8rem] truncate text-xs"
                                            >
                                                {{
                                                    item.attachments[0]
                                                        .original_filename
                                                }}
                                            </span>
                                        </a>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td class="px-3 py-2 text-end font-medium">
                                        {{ money(item.amount) }}
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <RowActionsMenu
                                            :actions="expenseActions(item)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3">
                        <MisPagination :pagination="props.expenses" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </V2ListPage>
</template>
