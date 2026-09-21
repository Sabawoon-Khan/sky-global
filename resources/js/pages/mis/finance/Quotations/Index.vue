<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisListFilterBar from '@/components/mis/MisListFilterBar.vue';
import MisPagination from '@/components/MisPagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { formatCurrency, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { ClipboardList, Plus, Printer, Trash2 } from '@lucide/vue';

interface Quotation {
    id: number;
    quote_number: string;
    quote_date?: string | null;
    valid_until?: string | null;
    status: string;
    total?: number | null;
    currency?: string | null;
    organization?: { id: number; name: string } | null;
}

interface SelectOption {
    id: number;
    name: string;
}

const props = defineProps<{
    quotations: Paginated<Quotation>;
    organizations?: SelectOption[];
    next_quote_number?: string;
    filters?: {
        search?: string | null;
        organization_id?: number | null;
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const { t, deleteAction } = useMisPage();

const { filters, apply, clear } = useMisFilters(
    '/finance/quotations',
    {
        search: props.filters?.search ?? '',
        organization_id: props.filters?.organization_id
            ? String(props.filters.organization_id)
            : '',
        status: props.filters?.status ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    {
        search: '',
        organization_id: '',
        status: '',
        date_from: '',
        date_to: '',
    },
    {
        only: ['quotations', 'organizations', 'filters'],
        liveKeys: ['search'],
    },
);

const hasActiveFilters = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.organization_id) ||
        Boolean(filters.status) ||
        Boolean(filters.date_from) ||
        Boolean(filters.date_to),
);

const { sortedRows } = provideTableSort(() => props.quotations.data, {
    accessors: {
        number: (row) => row.quote_number,
        client: (row) => row.organization?.name,
        date: (row) => row.quote_date,
        valid: (row) => row.valid_until,
        status: (row) => row.status,
        amount: (row) => row.total,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Quotations', href: '/finance/quotations' },
        ],
    },
});

const showForm = ref(false);
const tax = ref('');
const lines = ref([{ description: '', quantity: '1', unit_price: '' }]);

const subtotal = computed(() =>
    lines.value.reduce((sum, line) => {
        const quantity = Number(line.quantity) || 0;
        const unitPrice = Number(line.unit_price) || 0;

        return sum + quantity * unitPrice;
    }, 0),
);

const total = computed(() => subtotal.value + (Number(tax.value) || 0));

const addLine = (): void => {
    lines.value.push({ description: '', quantity: '1', unit_price: '' });
};

const removeLine = (index: number): void => {
    if (lines.value.length === 1) {
        lines.value[0] = { description: '', quantity: '1', unit_price: '' };
        return;
    }

    lines.value.splice(index, 1);
};

watch(showForm, (open) => {
    if (!open) {
        tax.value = '';
        lines.value = [{ description: '', quantity: '1', unit_price: '' }];
    }
});

const quotationActions = (quotation: Quotation): RowActionItem[] => [
    {
        label: t('Print'),
        icon: Printer,
        href: `/finance/quotations/${quotation.id}/print?autoprint=1`,
        download: true,
    },
    deleteAction(
        {
            href: `/finance/quotations/${quotation.id}`,
            title: t('Delete quotation'),
            description: t('Delete quotation :label? This cannot be undone.', {
                label: quotation.quote_number,
            }),
        },
        'finance.delete',
    ),
];
</script>

<template>
    <Head :title="t('Quotations')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Quotations') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Quotations')"
                        :value="String(quotations.total ?? 0)"
                    >
                        <template #icon><ClipboardList /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 class="text-base font-semibold tracking-tight">
                    {{ t('Quotations') }}
                </h2>
                <Can permission="finance.create">
                    <Button variant="outline" size="sm" @click="showForm = true">
                        <Plus class="me-1 size-4" />
                        {{ t('Add quotation') }}
                    </Button>
                </Can>
            </div>

            <MisListFilterBar
                v-model:search="filters.search"
                v-model:date-from="filters.date_from"
                v-model:date-to="filters.date_to"
                :search-placeholder="t('Search')"
                :has-active="hasActiveFilters"
                @apply="apply()"
                @clear="clear"
            >
                <V2SelectFilter
                    v-model="filters.organization_id"
                    :label="t('Client')"
                    @change="(value) => apply({ organization_id: value })"
                >
                    <option value="">{{ t('All') }}</option>
                    <option
                        v-for="org in organizations ?? []"
                        :key="org.id"
                        :value="String(org.id)"
                    >
                        {{ org.name }}
                    </option>
                </V2SelectFilter>
                <V2SelectFilter
                    v-model="filters.status"
                    :label="t('Status')"
                    @change="(value) => apply({ status: value })"
                >
                    <option value="">{{ t('All statuses') }}</option>
                    <option value="draft">{{ t('Draft') }}</option>
                    <option value="sent">{{ t('Sent') }}</option>
                    <option value="accepted">{{ t('Accepted') }}</option>
                    <option value="declined">{{ t('Declined') }}</option>
                    <option value="expired">{{ t('Expired') }}</option>
                </V2SelectFilter>
            </MisListFilterBar>

            <Dialog :open="showForm" @update:open="showForm = $event">
                <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <Form
                        action="/finance/quotations"
                        method="post"
                        :options="{
                            preserveScroll: true,
                            resetOnSuccess: true,
                        }"
                        v-slot="{ errors, processing }"
                        @success="showForm = false"
                    >
                        <DialogHeader>
                            <DialogTitle>{{ t('New quotation') }}</DialogTitle>
                        </DialogHeader>

                        <div class="grid gap-4 py-4">
                            <input type="hidden" name="currency" value="USD" />

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="q-number">{{
                                        t('Quote number')
                                    }}</Label>
                                    <Input
                                        id="q-number"
                                        name="quote_number"
                                        :default-value="next_quote_number"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            t(
                                                'Auto-generated. You can change this before saving.',
                                            )
                                        }}
                                    </p>
                                    <InputError :message="errors.quote_number" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="q-org">{{ t('Client') }}</Label>
                                    <select
                                        id="q-org"
                                        name="organization_id"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">
                                            {{ t('Select client') }}
                                        </option>
                                        <option
                                            v-for="org in organizations ?? []"
                                            :key="org.id"
                                            :value="org.id"
                                        >
                                            {{ org.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="q-date">{{ t('Date') }} *</Label>
                                    <Input
                                        id="q-date"
                                        name="quote_date"
                                        type="date"
                                        required
                                    />
                                    <InputError :message="errors.quote_date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="q-valid">{{
                                        t('Valid until')
                                    }}</Label>
                                    <Input
                                        id="q-valid"
                                        name="valid_until"
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="q-work">{{
                                        t('Description of work')
                                    }}</Label>
                                    <Textarea
                                        id="q-work"
                                        name="description_of_work"
                                        rows="3"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <Label>{{ t('Line items') }}</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="addLine"
                                    >
                                        <Plus class="size-4" />
                                    </Button>
                                </div>
                                <div class="overflow-x-auto rounded-md border">
                                    <table class="w-full text-sm">
                                        <thead class="bg-muted/40 text-muted-foreground">
                                            <tr>
                                                <th class="px-2 py-1.5 text-start font-medium">
                                                    {{ t('Description') }}
                                                </th>
                                                <th class="w-24 px-2 py-1.5 text-start font-medium">
                                                    {{ t('Qty') }}
                                                </th>
                                                <th class="w-32 px-2 py-1.5 text-start font-medium">
                                                    {{ t('Unit cost') }}
                                                </th>
                                                <th class="w-10" />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(line, index) in lines"
                                                :key="index"
                                            >
                                                <td class="px-2 py-1.5">
                                                    <Input
                                                        v-model="line.description"
                                                        :name="`line_items[${index}][description]`"
                                                        class="h-8"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <Input
                                                        v-model="line.quantity"
                                                        :name="`line_items[${index}][quantity]`"
                                                        type="number"
                                                        min="0"
                                                        step="1"
                                                        class="h-8"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <Input
                                                        v-model="line.unit_price"
                                                        :name="`line_items[${index}][unit_price]`"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="h-8"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <button
                                                        type="button"
                                                        class="text-muted-foreground hover:text-destructive"
                                                        @click="removeLine(index)"
                                                    >
                                                        <Trash2 class="size-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="q-tax">{{
                                        t('Withholding tax')
                                    }}</Label>
                                    <Input
                                        id="q-tax"
                                        name="tax"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model="tax"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label>{{ t('Total') }}</Label>
                                    <div
                                        class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm font-semibold tabular-nums"
                                    >
                                        {{ formatCurrency(total, 'USD') }}
                                    </div>
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="q-notes">{{ t('Notes') }}</Label>
                                    <Input id="q-notes" name="notes" />
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                @click="showForm = false"
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

            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="!quotations.data.length"
                        class="ui-empty-state p-8"
                    >
                        {{ t('No quotations found.') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40 text-start text-muted-foreground">
                                <tr>
                                    <SortableTh column="number" class="px-4 py-3 font-medium">
                                        {{ t('Quote #') }}
                                    </SortableTh>
                                    <SortableTh column="client" class="px-4 py-3 font-medium">
                                        {{ t('Client') }}
                                    </SortableTh>
                                    <SortableTh column="date" class="px-4 py-3 font-medium">
                                        {{ t('Date') }}
                                    </SortableTh>
                                    <SortableTh column="valid" class="px-4 py-3 font-medium">
                                        {{ t('Valid until') }}
                                    </SortableTh>
                                    <SortableTh column="status" class="px-4 py-3 font-medium">
                                        {{ t('Status') }}
                                    </SortableTh>
                                    <SortableTh
                                        column="amount"
                                        align="end"
                                        class="px-4 py-3 text-end font-medium"
                                    >
                                        {{ t('Amount') }}
                                    </SortableTh>
                                    <th class="px-4 py-3 text-end font-medium">
                                        {{ t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="quotation in sortedRows"
                                    :key="quotation.id"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        {{ quotation.quote_number }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ quotation.organization?.name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ formatDate(quotation.quote_date) }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ formatDate(quotation.valid_until) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge variant="outline">
                                            {{ quotation.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-end font-semibold tabular-nums">
                                        {{
                                            formatCurrency(
                                                quotation.total,
                                                quotation.currency,
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <RowActionsMenu
                                            :actions="quotationActions(quotation)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="border-t px-4 py-3">
                            <MisPagination :pagination="quotations" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </V2ListPage>
</template>
