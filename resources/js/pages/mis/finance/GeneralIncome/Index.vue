<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
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
import { Pencil, Plus, Wallet } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
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
    attachments?: FinanceAttachment[];
}

const props = defineProps<{
    generalIncomes: Paginated<GeneralRecord>;
    categories?: FinanceCategoryOption[];
    filters?: {
        search?: string | null;
        status?: string | null;
        category?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
    stats?: { total?: number; count?: number };
}>();

const { t, can, deleteAction } = useMisPage();
const showGeneralIncomeForm = ref(false);
const viewingRecord = ref<GeneralRecord | null>(null);
const editingRecord = ref<GeneralRecord | null>(null);

const openCreateIncome = (): void => {
    editingRecord.value = null;
    showGeneralIncomeForm.value = true;
};

const openEditIncome = (item: GeneralRecord): void => {
    viewingRecord.value = null;
    editingRecord.value = item;
    showGeneralIncomeForm.value = true;
};

const incomeActions = (item: GeneralRecord): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        hidden: !can('finance.edit'),
        onClick: () => openEditIncome(item),
    },
    deleteAction(
        {
            href: `/finance/general-incomes/${item.id}`,
            title: t('Delete income'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: item.description || t('this income') },
            ),
        },
        'finance.delete',
    ),
];

const { filters, apply, clear } = useMisFilters(
    '/finance/general-income',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        category: props.filters?.category ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    { search: '', status: '', category: '', date_from: '', date_to: '' },
    {
        only: ['generalIncomes', 'categories', 'filters', 'stats'],
        liveKeys: ['search'],
    },
);

const hasActiveFilters = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.status) ||
        Boolean(filters.category) ||
        Boolean(filters.date_from) ||
        Boolean(filters.date_to),
);

const { sortedRows } = provideTableSort(() => props.generalIncomes.data, {
    accessors: {
        description: (row) => row.description,
        category: (row) => row.category,
        date: (row) => row.transaction_date,
        attachment: (row) => row.attachments?.[0]?.original_filename,
        amount: (row) => row.amount,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Other Income', href: '/finance/general-income' },
        ],
    },
});

const money = (value?: number | null): string => formatAfn(value);
</script>

<template>
    <Head :title="t('Other Income')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Other Income') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Records')"
                        :value="
                            String(stats?.count ?? generalIncomes.total ?? 0)
                        "
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Total')"
                        :value="formatAfn(stats?.total)"
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
                            t('Other Income')
                        }}</CardTitle>
                    </div>
                    <Can permission="finance.create">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openCreateIncome"
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
                    <option value="recorded">{{ t('Recorded') }}</option>
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
            </MisListFilterBar>
            <CardContent class="space-y-4">
                <div
                    v-if="!props.generalIncomes.data.length"
                    class="ui-empty-state"
                >
                    {{ t('No other income records.') }}
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
                                        class="px-3 py-2 text-end font-medium text-green-600 dark:text-green-400"
                                    >
                                        {{ money(item.amount) }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-end"
                                        @click.stop
                                    >
                                        <RowActionsMenu
                                            :actions="incomeActions(item)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3">
                        <MisPagination :pagination="props.generalIncomes" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Dialog
            :open="showGeneralIncomeForm"
            @update:open="
                (open) => {
                    showGeneralIncomeForm = open;
                    if (!open) editingRecord = null;
                }
            "
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    :key="editingRecord?.id ?? 'create'"
                    :action="
                        editingRecord
                            ? `/finance/general-incomes/${editingRecord.id}`
                            : '/finance/general-incomes'
                    "
                    :method="editingRecord ? 'put' : 'post'"
                    :options="{
                        preserveScroll: true,
                        resetOnSuccess: true,
                        forceFormData: true,
                    }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="showGeneralIncomeForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingRecord
                                    ? t('Edit income')
                                    : t('Other Income')
                            }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="gi-description">{{
                                t('Description')
                            }}</Label>
                            <Textarea
                                id="gi-description"
                                name="description"
                                rows="2"
                                required
                                :default-value="editingRecord?.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <FinanceCategoryField
                                applies-to="income"
                                :categories="categories ?? []"
                                :error="errors.category"
                                :default-value="editingRecord?.category"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="gi-amount">{{ t('Amount') }} *</Label>
                            <Input
                                id="gi-amount"
                                name="amount"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                :default-value="editingRecord?.amount"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="gi-date">{{ t('Date') }} *</Label>
                            <Input
                                id="gi-date"
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
                            @click="showGeneralIncomeForm = false"
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
                            @click="openEditIncome(viewingRecord)"
                        >
                            {{ t('Edit') }}
                        </Button>
                    </Can>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
