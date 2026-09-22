<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import FileLink from '@/components/FileLink.vue';
import InputError from '@/components/InputError.vue';
import MisListFilterBar from '@/components/mis/MisListFilterBar.vue';
import MisPagination from '@/components/MisPagination.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import FinanceCategoryField, {
    type FinanceCategoryOption,
} from '@/components/FinanceCategoryField.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { V2Hero, V2ListPage, V2SelectFilter, V2StatCard, V2StatGrid } from '@/components/v2';
import SortableTh from '@/components/SortableTh.vue';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Pencil, Plus, Receipt, Wallet } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface ExpenseFundSummary {
    id: number;
    label: string;
    received_from: string | null;
    description: string | null;
    amount_received: number;
    spent_amount: number;
    remaining_amount: number;
    currency: string;
    received_date: string | null;
    reference_number: string | null;
    is_overdrawn: boolean;
    can_delete: boolean;
}

interface GeneralRecord {
    id: number;
    description: string | null;
    category?: string | null;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    transaction_date?: string | null;
    status?: string | null;
    expense_fund_id?: number | null;
    expense_fund_label?: string | null;
    attachments?: FinanceAttachment[];
}

const props = defineProps<{
    generalExpenses: Paginated<GeneralRecord>;
    expenseFunds?: ExpenseFundSummary[];
    expenseFundPickerOptions?: ExpenseFundSummary[];
    categories?: FinanceCategoryOption[];
    filters?: {
        search?: string | null;
        status?: string | null;
        category?: string | null;
        date_from?: string | null;
        date_to?: string | null;
        expense_fund_id?: string | null;
    };
    stats?: {
        total?: number;
        count?: number;
        funds_received_total?: number;
        open_funds_count?: number;
    };
}>();

const { t, can, deleteAction } = useMisPage();
const showGeneralExpenseForm = ref(false);
const showExpenseFundForm = ref(false);
const viewingRecord = ref<GeneralRecord | null>(null);
const editingRecord = ref<GeneralRecord | null>(null);
const viewingFund = ref<ExpenseFundSummary | null>(null);
const editingFund = ref(false);

const { filters, apply, clear } = useMisFilters(
    '/finance/general-expenses',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        category: props.filters?.category ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
        expense_fund_id: props.filters?.expense_fund_id ?? '',
    },
    {
        search: '',
        status: '',
        category: '',
        date_from: '',
        date_to: '',
        expense_fund_id: '',
    },
    {
        only: [
            'generalExpenses',
            'expenseFunds',
            'expenseFundPickerOptions',
            'categories',
            'filters',
            'stats',
        ],
        liveKeys: ['search'],
    },
);

const hasActiveFilters = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.status) ||
        Boolean(filters.category) ||
        Boolean(filters.date_from) ||
        Boolean(filters.date_to) ||
        Boolean(filters.expense_fund_id),
);

const fundOptions = computed(() => props.expenseFunds ?? []);

/** Funds with remaining balance (depleted funds are excluded). */
const spendFromFundOptions = computed(
    () => props.expenseFundPickerOptions ?? [],
);

const fundLabelWithRemaining = (fund: ExpenseFundSummary): string => {
    return `${fund.label} — ${formatAfn(fund.remaining_amount)} ${t('left')}`;
};

const { sortedRows } = provideTableSort(() => props.generalExpenses.data, {
    accessors: {
        description: (row) => row.description,
        category: (row) => row.category,
        fund: (row) => row.expense_fund_label,
        date: (row) => row.transaction_date,
        attachment: (row) => row.attachments?.[0]?.original_filename,
        amount: (row) => row.amount,
    },
});

function openFundDetail(fund: ExpenseFundSummary): void {
    viewingFund.value = fund;
    editingFund.value = false;
}

const openCreateExpense = (): void => {
    editingRecord.value = null;
    showGeneralExpenseForm.value = true;
};

const openEditExpense = (item: GeneralRecord): void => {
    viewingRecord.value = null;
    editingRecord.value = item;
    showGeneralExpenseForm.value = true;
};

const expenseActions = (item: GeneralRecord): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        hidden: !can('finance.edit'),
        onClick: () => openEditExpense(item),
    },
    deleteAction(
        {
            href: `/finance/general-expenses/${item.id}`,
            title: t('Delete expense'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description || t('this expense') },
            ),
        },
        'finance.delete',
    ),
];

function deleteFund(fund: ExpenseFundSummary): void {
    if (!fund.can_delete) {
        return;
    }
    if (!window.confirm(t('Delete this expense fund?'))) {
        return;
    }
    router.delete(`/finance/expense-funds/${fund.id}`, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            {
                title: 'Overhead & Salaries',
                href: '/finance/general-expenses',
            },
        ],
    },
});

const money = (value?: number | null): string => formatAfn(value);
</script>

<template>
    <Head :title="t('Overhead & Salaries')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Overhead & Salaries') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        icon-tone="warm"
                        :title="t('Records')"
                        :value="
                            String(stats?.count ?? generalExpenses.total ?? 0)
                        "
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="orange"
                        :title="t('Total spent')"
                        :value="formatAfn(stats?.total)"
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Open funds')"
                        :value="String(stats?.open_funds_count ?? 0)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <Card>
            <CardHeader class="pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">{{
                            t('Expense funds')
                        }}</CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'Record money received, then link overhead or project expenses to that fund.',
                                )
                            }}
                        </p>
                    </div>
                    <Can permission="finance.create">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showExpenseFundForm = true"
                        >
                            <Plus class="me-1 size-4" />
                            {{ t('Record received') }}
                        </Button>
                    </Can>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="!fundOptions.length"
                    class="ui-empty-state"
                >
                    {{ t('No expense funds yet.') }}
                </div>
                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/40 text-start text-muted-foreground"
                        >
                            <tr>
                                <th class="px-3 py-2 text-start font-medium">
                                    {{ t('Date') }}
                                </th>
                                <th class="px-3 py-2 text-start font-medium">
                                    {{ t('Received from') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Received') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Spent') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-end font-medium"
                                >
                                    {{ t('Remaining') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="fund in fundOptions"
                                :key="fund.id"
                                class="cursor-pointer hover:bg-muted/30"
                                @click="openFundDetail(fund)"
                            >
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(fund.received_date) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ fund.received_from || fund.label }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end tabular-nums font-medium"
                                >
                                    {{ money(fund.amount_received) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end tabular-nums text-muted-foreground"
                                >
                                    {{ money(fund.spent_amount) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-end tabular-nums font-medium"
                                    :class="
                                        fund.is_overdrawn
                                            ? 'text-amber-600 dark:text-amber-400'
                                            : fund.remaining_amount <= 0
                                              ? 'text-muted-foreground'
                                              : 'text-emerald-600 dark:text-emerald-400'
                                    "
                                >
                                    {{ money(fund.remaining_amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">{{
                            t('Overhead & Salaries')
                        }}</CardTitle>
                    </div>
                    <Can permission="finance.create">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openCreateExpense"
                        >
                            <Plus class="me-1 size-4" />
                            {{ t('Add') }}
                        </Button>
                    </Can>
                </div>
            </CardHeader>
            <MisListFilterBar
                v-model:search="filters.search"
                v-model:date-from="filters.date_from"
                v-model:date-to="filters.date_to"
                :search-placeholder="t('Search description, project, or reference...')"
                :has-active="hasActiveFilters"
                @apply="apply()"
                @clear="clear"
            >
                <V2SelectFilter
                    v-model="filters.status"
                    :label="t('Status')"
                    @change="(value) => apply({ status: value })"
                >
                    <option value="">{{ t('All statuses') }}</option>
                    <option value="pending">{{ t('Pending') }}</option>
                    <option value="approved">{{ t('Approved') }}</option>
                    <option value="rejected">{{ t('Rejected') }}</option>
                </V2SelectFilter>
                <V2SelectFilter
                    v-model="filters.category"
                    :label="t('Category')"
                    @change="(value) => apply({ category: value })"
                >
                    <option value="">{{ t('All categories') }}</option>
                    <option
                        v-for="category in categories ?? []"
                        :key="category.id"
                        :value="category.name"
                    >
                        {{ category.name }}
                    </option>
                </V2SelectFilter>
                <V2SelectFilter
                    v-model="filters.expense_fund_id"
                    :label="t('Fund')"
                    @change="(value) => apply({ expense_fund_id: value })"
                >
                    <option value="">{{ t('All funds') }}</option>
                    <option
                        v-for="fund in fundOptions"
                        :key="fund.id"
                        :value="String(fund.id)"
                    >
                        {{ fund.label }}
                    </option>
                </V2SelectFilter>
            </MisListFilterBar>
            <CardContent class="space-y-4">
                <div
                    v-if="!props.generalExpenses.data.length"
                    class="ui-empty-state"
                >
                    {{ t('No overhead records.') }}
                </div>
                <div v-else class="space-y-0">
                    <div class="overflow-x-auto rounded-md border">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b bg-muted/40 text-start text-muted-foreground"
                            >
                                <tr>
                                    <SortableTh
                                        column="description"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t('Description') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="category"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t('Category') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="fund"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t('Fund') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="date"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t('Date') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="attachment"
                                        class="px-3 py-2 font-medium"
                                    >
                                        {{ t('Attachment') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="amount"
                                        align="end"
                                        class="px-3 py-2 text-end font-medium"
                                    >
                                        {{ t('Amount') }}
                                    </SortableTh>
                                    <th class="w-12 px-3 py-2" />
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
                                    <td
                                        class="px-3 py-2 text-muted-foreground"
                                    >
                                        {{ item.category ?? '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-muted-foreground"
                                    >
                                        {{ item.expense_fund_label ?? '—' }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-muted-foreground"
                                    >
                                        {{
                                            formatDate(item.transaction_date)
                                        }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <FileLink
                                            v-if="item.attachments?.length"
                                            :href="
                                                item.attachments[0]
                                                    .download_url
                                            "
                                            :label="
                                                item.attachments[0]
                                                    .original_filename
                                            "
                                            show-icon
                                            compact
                                        />
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td
                                        class="px-3 py-2 text-end font-medium text-destructive"
                                    >
                                        {{ money(item.amount) }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-end"
                                        @click.stop
                                    >
                                        <RowActionsMenu
                                            :actions="expenseActions(item)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3">
                        <MisPagination :pagination="props.generalExpenses" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Dialog
            :open="showExpenseFundForm"
            @update:open="showExpenseFundForm = $event"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    action="/finance/expense-funds"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="showExpenseFundForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Record received amount') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="ef-amount">{{ t('Amount') }} *</Label>
                            <Input
                                id="ef-amount"
                                name="amount_received"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors.amount_received" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ef-date">{{ t('Date') }} *</Label>
                            <Input
                                id="ef-date"
                                name="received_date"
                                type="date"
                                required
                            />
                            <InputError :message="errors.received_date" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="ef-from">{{ t('Received from') }}</Label>
                            <Input
                                id="ef-from"
                                name="received_from"
                                type="text"
                                :placeholder="t('e.g. Finance manager')"
                            />
                            <InputError :message="errors.received_from" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="ef-description">{{
                                t('Description')
                            }}</Label>
                            <Textarea
                                id="ef-description"
                                name="description"
                                rows="2"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="ef-reference">{{
                                t('Reference')
                            }}</Label>
                            <Input
                                id="ef-reference"
                                name="reference_number"
                                type="text"
                            />
                            <InputError :message="errors.reference_number" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showExpenseFundForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="viewingFund !== null"
            @update:open="(open) => !open && (viewingFund = null)"
        >
            <DialogContent v-if="viewingFund" class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ viewingFund.label }}</DialogTitle>
                </DialogHeader>

                <div v-if="!editingFund" class="space-y-4 py-2">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('Received from') }}
                            </dt>
                            <dd class="font-medium">
                                {{ viewingFund.received_from ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                            <dd class="font-medium">
                                {{ formatDate(viewingFund.received_date) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('Received') }}
                            </dt>
                            <dd class="font-medium tabular-nums">
                                {{ money(viewingFund.amount_received) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Spent') }}</dt>
                            <dd class="font-medium tabular-nums">
                                {{ money(viewingFund.spent_amount) }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground">
                                {{ t('Remaining') }}
                            </dt>
                            <dd
                                class="font-medium tabular-nums"
                                :class="
                                    viewingFund.is_overdrawn
                                        ? 'text-amber-600'
                                        : ''
                                "
                            >
                                {{ money(viewingFund.remaining_amount) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <Form
                    v-else
                    :action="`/finance/expense-funds/${viewingFund.id}`"
                    method="put"
                    :options="{ preserveScroll: true }"
                    v-slot="{ errors, processing }"
                    @success="
                        viewingFund = null;
                        editingFund = false;
                    "
                >
                    <div class="grid gap-3 py-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>{{ t('Amount') }} *</Label>
                            <Input
                                name="amount_received"
                                type="number"
                                min="0"
                                step="0.01"
                                :default-value="viewingFund.amount_received"
                                required
                            />
                            <InputError :message="errors.amount_received" />
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Date') }} *</Label>
                            <Input
                                name="received_date"
                                type="date"
                                :default-value="viewingFund.received_date ?? ''"
                                required
                            />
                            <InputError :message="errors.received_date" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label>{{ t('Received from') }}</Label>
                            <Input
                                name="received_from"
                                type="text"
                                :default-value="viewingFund.received_from ?? ''"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label>{{ t('Description') }}</Label>
                            <Textarea
                                name="description"
                                rows="2"
                                :default-value="viewingFund.description ?? ''"
                            />
                        </div>
                    </div>
                    <DialogFooter class="mt-4 gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="editingFund = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save') }}
                        </Button>
                    </DialogFooter>
                </Form>

                <DialogFooter v-if="!editingFund" class="gap-2">
                    <Can permission="finance.delete">
                        <Button
                            v-if="viewingFund.can_delete"
                            type="button"
                            variant="destructive"
                            @click="deleteFund(viewingFund)"
                        >
                            {{ t('Delete') }}
                        </Button>
                    </Can>
                    <Can permission="finance.edit">
                        <Button
                            type="button"
                            variant="outline"
                            @click="editingFund = true"
                        >
                            {{ t('Edit') }}
                        </Button>
                    </Can>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="viewingFund = null"
                    >
                        {{ t('Close') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showGeneralExpenseForm"
            @update:open="
                (open) => {
                    showGeneralExpenseForm = open;
                    if (!open) editingRecord = null;
                }
            "
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    :key="editingRecord?.id ?? 'create'"
                    :action="
                        editingRecord
                            ? `/finance/general-expenses/${editingRecord.id}`
                            : '/finance/general-expenses'
                    "
                    :method="editingRecord ? 'put' : 'post'"
                    :options="{
                        preserveScroll: true,
                        resetOnSuccess: true,
                        forceFormData: true,
                    }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="showGeneralExpenseForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingRecord
                                    ? t('Edit expense')
                                    : t('Overhead & Salaries')
                            }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="ge-description">{{
                                t('Description')
                            }}</Label>
                            <Textarea
                                id="ge-description"
                                name="description"
                                rows="2"
                                required
                                :default-value="editingRecord?.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <FinanceCategoryField
                                applies-to="expense"
                                :categories="categories ?? []"
                                :error="errors.category"
                                :default-value="editingRecord?.category"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="ge-fund">{{ t('Spend from fund') }}</Label>
                            <select
                                id="ge-fund"
                                name="expense_fund_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="">
                                    {{ t('Not linked to a fund') }}
                                </option>
                                <option
                                    v-for="fund in spendFromFundOptions"
                                    :key="fund.id"
                                    :value="String(fund.id)"
                                    :selected="
                                        editingRecord?.expense_fund_id ===
                                        fund.id
                                    "
                                >
                                    {{ fundLabelWithRemaining(fund) }}
                                </option>
                            </select>
                            <InputError :message="errors.expense_fund_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ge-amount">{{ t('Amount') }} *</Label>
                            <Input
                                id="ge-amount"
                                name="amount"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                :default-value="editingRecord?.amount"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ge-date">{{ t('Date') }} *</Label>
                            <Input
                                id="ge-date"
                                name="transaction_date"
                                type="date"
                                required
                                :default-value="editingRecord?.transaction_date"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <OptionalAttachmentField
                                :label="t('Receipt')"
                                :error="errors.attachment"
                            />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showGeneralExpenseForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

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
                            <dt class="text-muted-foreground">{{ t('Category') }}</dt>
                            <dd class="font-medium">
                                {{ viewingRecord.category ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Fund') }}</dt>
                            <dd class="font-medium">
                                {{ viewingRecord.expense_fund_label ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                            <dd class="font-medium">
                                {{ formatDate(viewingRecord.transaction_date) }}
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
                        <div class="flex flex-col gap-1">
                            <FileLink
                                v-for="file in viewingRecord.attachments"
                                :key="file.id"
                                :href="file.download_url"
                                :label="file.original_filename"
                                show-icon
                                class="text-sm"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="viewingRecord = null"
                    >
                        {{ t('Close') }}
                    </Button>
                    <Can permission="finance.edit">
                        <Button
                            type="button"
                            variant="outline"
                            @click="openEditExpense(viewingRecord)"
                        >
                            {{ t('Edit') }}
                        </Button>
                    </Can>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
