<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import SortableTh from '@/components/SortableTh.vue';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
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
const viewingRecord = ref<Expense | null>(null);

const { sortedRows } = provideTableSort(() => props.expenses.data, {
    accessors: {
        description: (row) => row.description,
        project: (row) => row.project?.code ?? row.project?.name,
        date: (row) => row.transaction_date,
        status: (row) => row.status,
        attachment: (row) => row.attachments?.[0]?.original_filename,
        amount: (row) => row.amount,
    },
});

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
                                    <SortableTh column="description" class="px-3 py-2 font-medium">
                                        {{ t('Description') }}
                                    </SortableTh>
                                    <SortableTh column="project" class="px-3 py-2 font-medium">
                                        {{ t('Project') }}
                                    </SortableTh>
                                    <SortableTh column="date" class="px-3 py-2 font-medium">
                                        {{ t('Date') }}
                                    </SortableTh>
                                    <SortableTh column="status" class="px-3 py-2 font-medium">
                                        {{ t('Status') }}
                                    </SortableTh>
                                    <SortableTh column="attachment" class="px-3 py-2 font-medium">
                                        {{ t('Attachment') }}
                                    </SortableTh>
                                    <SortableTh column="amount" align="end" class="px-3 py-2 text-end font-medium">
                                        {{ t('Amount') }}
                                    </SortableTh>
                                    <th class="px-3 py-2 text-end font-medium">
                                        {{ t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="item in sortedRows"
                                    :key="item.id"
                                    class="cursor-pointer hover:bg-muted/30"
                                    @click="viewingRecord = item"
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
                                            @click.stop
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
                                    <td class="px-3 py-2 text-end" @click.stop>
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

        <Dialog
            :open="viewingRecord !== null"
            @update:open="(open) => !open && (viewingRecord = null)"
        >
            <DialogContent v-if="viewingRecord" class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('Description') }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed">
                        {{ viewingRecord.description || '—' }}
                    </p>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">{{ t('Project') }}</dt>
                            <dd class="font-medium">
                                {{ viewingRecord.project?.code ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                            <dd class="font-medium">
                                {{ formatDate(viewingRecord.transaction_date) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Status') }}</dt>
                            <dd>
                                <Badge variant="outline">
                                    {{ viewingRecord.status ?? t('pending') }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Amount') }}</dt>
                            <dd class="font-medium tabular-nums">
                                {{ money(viewingRecord.amount) }}
                            </dd>
                        </div>
                    </dl>
                    <div v-if="viewingRecord.attachments?.length">
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('Attachment') }}
                        </p>
                        <a
                            :href="viewingRecord.attachments[0].download_url"
                            class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                        >
                            <Paperclip class="size-3.5 shrink-0" />
                            {{ viewingRecord.attachments[0].original_filename }}
                        </a>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="viewingRecord = null"
                    >
                        {{ t('Close') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
