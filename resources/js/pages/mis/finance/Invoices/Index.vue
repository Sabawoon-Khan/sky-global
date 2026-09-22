<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import FileLink from '@/components/FileLink.vue';
import InputError from '@/components/InputError.vue';
import MisListFilterBar from '@/components/mis/MisListFilterBar.vue';
import MisPagination from '@/components/MisPagination.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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
import { V2Hero, V2ListPage, V2SelectFilter, V2StatCard, V2StatGrid } from '@/components/v2';
import SortableTh from '@/components/SortableTh.vue';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatCurrency, formatDate, formatNumber, type Paginated } from '@/lib/format';
import { proratedInvoiceLineTotal } from '@/lib/invoice-proration';
import type { RowActionItem } from '@/lib/row-actions';
import { invoiceStatusActions } from '@/lib/status-actions';
import { FileText, Pencil, Plus, Printer, Trash2 } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface InvoiceLine {
    description?: string | null;
    quantity?: number | string | null;
    unit_price?: number | string | null;
    days?: number | string | null;
    total?: number | null;
}

interface Invoice {
    id: number;
    invoice_number?: string | null;
    status: string;
    total?: number | null;
    subtotal?: number | null;
    tax?: number | null;
    currency?: string | null;
    issue_date?: string | null;
    due_date?: string | null;
    period_start?: string | null;
    period_end?: string | null;
    services?: string | null;
    notes?: string | null;
    project?: { id: number; code: string; name: string } | null;
    organization?: { id: number; name: string } | null;
    attachments?: FinanceAttachment[];
    line_items?: InvoiceLine[];
}

interface SelectOption {
    id: number;
    code?: string;
    name: string;
}

const props = defineProps<{
    invoices: Paginated<Invoice>;
    projects?: SelectOption[];
    organizations?: SelectOption[];
    next_invoice_number?: string;
    filters?: {
        search?: string | null;
        project_id?: number | null;
        organization_id?: number | null;
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const { t, can, deleteAction, gateActions } = useMisPage();

const { filters, apply, clear } = useMisFilters(
    '/finance/invoices',
    {
        search: props.filters?.search ?? '',
        project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
        organization_id: props.filters?.organization_id
            ? String(props.filters.organization_id)
            : '',
        status: props.filters?.status ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    {
        search: '',
        project_id: '',
        organization_id: '',
        status: '',
        date_from: '',
        date_to: '',
    },
    {
        only: ['invoices', 'projects', 'organizations', 'filters'],
        liveKeys: ['search'],
    },
);

const hasActiveFilters = computed(
    () =>
        Boolean(filters.search) ||
        Boolean(filters.project_id) ||
        Boolean(filters.organization_id) ||
        Boolean(filters.status) ||
        Boolean(filters.date_from) ||
        Boolean(filters.date_to),
);

const { sortedRows } = provideTableSort(() => props.invoices.data, {
    accessors: {
        number: (row) => row.invoice_number ?? row.id,
        client: (row) => row.organization?.name,
        project: (row) => row.project?.code ?? row.project?.name,
        due: (row) => row.due_date,
        status: (row) => row.status,
        attachment: (row) => row.attachments?.[0]?.original_filename,
        amount: (row) => row.total ?? row.subtotal,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Invoices', href: '/finance/invoices' },
        ],
    },
});

const showInvoiceForm = ref(false);
const editingInvoice = ref<Invoice | null>(null);
const invoiceTax = ref('');
const periodStart = ref('');
const periodEnd = ref('');
const invoiceLines = ref([
    { description: '', quantity: '1', unit_price: '', days: '1' },
]);

const periodDays = computed(() => {
    if (!periodStart.value || !periodEnd.value) {
        return null;
    }

    const start = new Date(`${periodStart.value}T00:00:00`);
    const end = new Date(`${periodEnd.value}T00:00:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
        return null;
    }

    return Math.round((end.getTime() - start.getTime()) / 86_400_000) + 1;
});

const defaultLineDays = (): string => String(periodDays.value ?? 1);

const lineTotal = (line: { quantity: string; unit_price: string; days: string }): number => {
    const quantity = Number(line.quantity) || 0;
    const unitPrice = Number(line.unit_price) || 0;
    const days = Number(line.days) || 1;

    return proratedInvoiceLineTotal(
        unitPrice,
        quantity,
        days,
        periodStart.value || null,
        periodEnd.value || null,
        periodStart.value ||
            periodEnd.value ||
            editingInvoice.value?.issue_date ||
            null,
    );
};

const invoiceSubtotal = computed(() =>
    invoiceLines.value.reduce((sum, line) => sum + lineTotal(line), 0),
);

const invoiceTotal = computed(
    () => invoiceSubtotal.value + (Number(invoiceTax.value) || 0),
);

const money = (value: number): string =>
    `$${formatNumber(value, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const applyPeriodDays = (days: number): void => {
    invoiceLines.value = invoiceLines.value.map((line) => ({
        ...line,
        days: String(days),
    }));
};

const addInvoiceLine = (): void => {
    invoiceLines.value.push({
        description: '',
        quantity: '1',
        unit_price: '',
        days: defaultLineDays(),
    });
};

const removeInvoiceLine = (index: number): void => {
    if (invoiceLines.value.length === 1) {
        invoiceLines.value[0] = {
            description: '',
            quantity: '1',
            unit_price: '',
            days: defaultLineDays(),
        };
        return;
    }

    invoiceLines.value.splice(index, 1);
};

watch(periodDays, (days) => {
    if (days) {
        applyPeriodDays(days);
    }
});

const resetInvoiceForm = (): void => {
    editingInvoice.value = null;
    invoiceTax.value = '';
    periodStart.value = '';
    periodEnd.value = '';
    invoiceLines.value = [
        { description: '', quantity: '1', unit_price: '', days: '1' },
    ];
};

const invoiceDialogOpenedAt = ref(0);

const openInvoiceDialog = (): void => {
    invoiceDialogOpenedAt.value = Date.now();
    showInvoiceForm.value = true;
};

const onInvoiceDialogOpenChange = (open: boolean): void => {
    if (!open && Date.now() - invoiceDialogOpenedAt.value < 300) {
        return;
    }

    showInvoiceForm.value = open;
};

const guardInvoiceDialogDismiss = (event: Event): void => {
    if (Date.now() - invoiceDialogOpenedAt.value < 300) {
        event.preventDefault();
    }
};

const openCreateInvoice = (): void => {
    resetInvoiceForm();
    openInvoiceDialog();
};

const openEditInvoice = (invoice: Invoice): void => {
    editingInvoice.value = invoice;
    invoiceTax.value = invoice.tax ? String(invoice.tax) : '';
    periodStart.value = invoice.period_start ?? '';
    periodEnd.value = invoice.period_end ?? '';
    invoiceLines.value = (invoice.line_items ?? []).length
        ? invoice.line_items!.map((line) => ({
              description: line.description ?? '',
              quantity: String(line.quantity ?? 1),
              unit_price: String(line.unit_price ?? ''),
              days: String(line.days ?? 1),
          }))
        : [
              {
                  description: invoice.services ?? '',
                  quantity: '1',
                  unit_price: '',
                  days: '1',
              },
          ];
    void nextTick(() => {
        openInvoiceDialog();
    });
};

watch(showInvoiceForm, (open) => {
    if (!open) {
        resetInvoiceForm();
    }
});

const invoiceStatusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'paid') {
        return 'default';
    }
    if (status === 'overdue') {
        return 'destructive';
    }
    if (status === 'sent') {
        return 'secondary';
    }
    return 'outline';
};

const invoiceStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        draft: t('Draft'),
        sent: t('Sent'),
        paid: t('Paid'),
        overdue: t('Overdue'),
        cancelled: t('Cancelled'),
    };
    return labels[status] ?? status;
};

const invoiceActions = (invoice: Invoice): RowActionItem[] => [
    {
        label: t('Print'),
        icon: Printer,
        href: `/finance/invoices/${invoice.id}/print?autoprint=1`,
        download: true,
    },
    {
        label: t('Edit'),
        icon: Pencil,
        hidden: !can('finance.edit'),
        onClick: () => openEditInvoice(invoice),
    },
    ...gateActions(
        invoiceStatusActions({
            url: `/finance/invoices/${invoice.id}`,
            label: invoice.invoice_number ?? `#${invoice.id}`,
            status: invoice.status,
            t,
        }),
        'finance.edit',
    ),
    deleteAction(
        {
            href: `/finance/invoices/${invoice.id}`,
            title: t('Delete invoice'),
            description: t('Delete invoice :label? This cannot be undone.', {
                label: invoice.invoice_number ?? `#${invoice.id}`,
            }),
        },
        'finance.delete',
    ),
];
</script>

<template>
    <Head :title="t('Invoices')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Invoices') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Invoices')"
                        :value="String(invoices.total ?? 0)"
                    >
                        <template #icon><FileText /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold tracking-tight">
                        {{ t('Invoices') }}
                    </h2>
                </div>
                <Can permission="finance.create">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="openCreateInvoice"
                    >
                        <Plus class="me-1 size-4" />
                        {{ t('Add invoice') }}
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
                    v-model="filters.project_id"
                    :label="t('Project')"
                    @change="(value) => apply({ project_id: value })"
                >
                    <option value="">{{ t('All projects') }}</option>
                    <option
                        v-for="project in projects ?? []"
                        :key="project.id"
                        :value="String(project.id)"
                    >
                        {{ project.code ?? project.name }}
                    </option>
                </V2SelectFilter>
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
                    <option value="paid">{{ t('Paid') }}</option>
                    <option value="overdue">{{ t('Overdue') }}</option>
                    <option value="cancelled">{{ t('Cancelled') }}</option>
                </V2SelectFilter>
            </MisListFilterBar>

            <Dialog
                :open="showInvoiceForm"
                @update:open="onInvoiceDialogOpenChange"
            >
                <DialogContent
                    class="max-h-[90vh] overflow-y-auto sm:max-w-3xl"
                    @pointer-down-outside="guardInvoiceDialogDismiss"
                    @interact-outside="guardInvoiceDialogDismiss"
                >
                    <Form
                        :key="editingInvoice?.id ?? 'create'"
                        :action="
                            editingInvoice
                                ? `/finance/invoices/${editingInvoice.id}`
                                : '/finance/invoices'
                        "
                        :method="editingInvoice ? 'put' : 'post'"
                        :options="{
                            preserveScroll: true,
                            resetOnSuccess: true,
                            forceFormData: true,
                        }"
                        validate-files
                        v-slot="{ errors, processing }"
                        @success="showInvoiceForm = false"
                    >
                        <DialogHeader>
                            <DialogTitle>
                                {{
                                    editingInvoice
                                        ? t('Edit invoice')
                                        : t('New invoice')
                                }}
                            </DialogTitle>
                        </DialogHeader>

                        <div class="grid gap-4 py-4">
                            <input type="hidden" name="currency" value="USD" />
                            <input
                                type="hidden"
                                name="subtotal"
                                :value="invoiceSubtotal.toFixed(2)"
                            />
                            <input
                                type="hidden"
                                name="total"
                                :value="invoiceTotal.toFixed(2)"
                            />
                            <input
                                type="hidden"
                                name="line_items_json"
                                :value="JSON.stringify(invoiceLines)"
                            />

                            <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="inv-number">{{
                                    t('Invoice number')
                                }}</Label>
                                <Input
                                    id="inv-number"
                                    name="invoice_number"
                                    :default-value="
                                        editingInvoice?.invoice_number ??
                                        next_invoice_number
                                    "
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t(
                                            'Auto-generated. You can change this before saving.',
                                        )
                                    }}
                                </p>
                                <InputError :message="errors.invoice_number" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-status">{{
                                    t('Status')
                                }}</Label>
                                <select
                                    id="inv-status"
                                    name="status"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option
                                        value="draft"
                                        :selected="
                                            (editingInvoice?.status ??
                                                'draft') === 'draft'
                                        "
                                    >
                                        {{ t('Draft') }}
                                    </option>
                                    <option
                                        value="sent"
                                        :selected="
                                            editingInvoice?.status === 'sent'
                                        "
                                    >
                                        {{ t('Sent') }}
                                    </option>
                                    <option
                                        value="paid"
                                        :selected="
                                            editingInvoice?.status === 'paid'
                                        "
                                    >
                                        {{ t('Paid') }}
                                    </option>
                                    <option
                                        value="overdue"
                                        :selected="
                                            editingInvoice?.status ===
                                            'overdue'
                                        "
                                    >
                                        {{ t('Overdue') }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-org">{{ t('Client') }}</Label>
                                <select
                                    id="inv-org"
                                    name="organization_id"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">
                                        {{ t('Select client') }}
                                    </option>
                                    <option
                                        v-for="org in props.organizations ?? []"
                                        :key="org.id"
                                        :value="org.id"
                                        :selected="
                                            editingInvoice?.organization
                                                ?.id === org.id
                                        "
                                    >
                                        {{ org.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-project">{{
                                    t('Project')
                                }}</Label>
                                <select
                                    id="inv-project"
                                    name="project_id"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">
                                        {{ t('Optional project') }}
                                    </option>
                                    <option
                                        v-for="project in props.projects ?? []"
                                        :key="project.id"
                                        :value="project.id"
                                        :selected="
                                            editingInvoice?.project?.id ===
                                            project.id
                                        "
                                    >
                                        {{ project.code }} — {{ project.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-issue"
                                    >{{ t('Issue date') }} *</Label
                                >
                                <Input
                                    id="inv-issue"
                                    name="issue_date"
                                    type="date"
                                    required
                                    :default-value="editingInvoice?.issue_date"
                                />
                                <InputError :message="errors.issue_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-due">{{
                                    t('Due date')
                                }}</Label>
                                <Input
                                    id="inv-due"
                                    name="due_date"
                                    type="date"
                                    :default-value="editingInvoice?.due_date"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-period-start">{{
                                    t('Period start')
                                }}</Label>
                                <Input
                                    id="inv-period-start"
                                    name="period_start"
                                    type="date"
                                    v-model="periodStart"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-period-end">{{
                                    t('Period end')
                                }}</Label>
                                <Input
                                    id="inv-period-end"
                                    name="period_end"
                                    type="date"
                                    v-model="periodEnd"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="inv-services">{{
                                    t('Services')
                                }}</Label>
                                <Input
                                    id="inv-services"
                                    name="services"
                                    :default-value="editingInvoice?.services"
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
                                        @click="addInvoiceLine"
                                    >
                                        <Plus class="size-4" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        t(
                                            'Days come from the invoice period. Unit cost is a monthly rate divided by that month’s length (28–31 days): (unit cost ÷ days in month) × quantity × days. Periods spanning multiple months are split by calendar month.',
                                        )
                                    }}
                                </p>
                                <div class="overflow-x-auto rounded-md border">
                                    <table class="w-full text-sm">
                                        <thead class="bg-muted/40 text-muted-foreground">
                                            <tr>
                                                <th class="px-2 py-1.5 text-start font-medium">
                                                    {{ t('Description') }}
                                                </th>
                                                <th class="w-20 px-2 py-1.5 text-start font-medium">
                                                    {{ t('Qty') }}
                                                </th>
                                                <th class="w-28 px-2 py-1.5 text-start font-medium">
                                                    {{ t('Unit cost') }}
                                                </th>
                                                <th class="w-20 px-2 py-1.5 text-start font-medium">
                                                    {{ t('Days') }}
                                                </th>
                                                <th class="w-24 px-2 py-1.5 text-end font-medium">
                                                    {{ t('Total') }}
                                                </th>
                                                <th class="w-10" />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(line, index) in invoiceLines"
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
                                                    <Input
                                                        v-model="line.days"
                                                        :name="`line_items[${index}][days]`"
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        class="h-8"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5 text-end tabular-nums">
                                                    {{
                                                        line.description
                                                            ? money(lineTotal(line))
                                                            : ''
                                                    }}
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <button
                                                        type="button"
                                                        class="text-muted-foreground hover:text-destructive"
                                                        @click="removeInvoiceLine(index)"
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
                                <Label for="inv-tax">{{ t('Tax') }}</Label>
                                <Input
                                    id="inv-tax"
                                    name="tax"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    v-model="invoiceTax"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>{{ t('Total') }}</Label>
                                <div
                                    class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm font-semibold tabular-nums"
                                >
                                    {{ money(invoiceTotal) }}
                                </div>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="inv-notes">{{ t('Notes') }}</Label>
                                <Input
                                    id="inv-notes"
                                    name="notes"
                                    :default-value="editingInvoice?.notes"
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <OptionalAttachmentField
                                    :label="t('Attachment')"
                                    :error="errors.attachment"
                                />
                            </div>
                            </div>
                        </div>

                        <DialogFooter class="gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                @click="showInvoiceForm = false"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button type="submit" :disabled="processing">
                                {{ t('Save invoice') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>

            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="!props.invoices?.data?.length"
                        class="ui-empty-state p-8"
                    >
                        {{ t('No invoices found.') }}
                    </div>
                    <div v-else class="space-y-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b bg-muted/40 text-start text-muted-foreground"
                                >
                                    <tr>
                                        <SortableTh column="number" class="px-4 py-3 font-medium">
                                            {{ t('Invoice #') }}
                                        </SortableTh>
                                        <SortableTh column="client" class="px-4 py-3 font-medium">
                                            {{ t('Client') }}
                                        </SortableTh>
                                        <SortableTh column="project" class="px-4 py-3 font-medium">
                                            {{ t('Project') }}
                                        </SortableTh>
                                        <SortableTh column="due" class="px-4 py-3 font-medium">
                                            {{ t('Due Date') }}
                                        </SortableTh>
                                        <SortableTh column="status" class="px-4 py-3 font-medium">
                                            {{ t('Status') }}
                                        </SortableTh>
                                        <SortableTh column="attachment" class="px-4 py-3 font-medium">
                                            {{ t('Attachment') }}
                                        </SortableTh>
                                        <SortableTh column="amount" align="end" class="px-4 py-3 text-end font-medium">
                                            {{ t('Amount') }}
                                        </SortableTh>
                                        <th class="px-4 py-3 text-end font-medium">
                                            {{ t('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="invoice in sortedRows"
                                        :key="invoice.id"
                                        class="hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-3 font-medium">
                                            {{
                                                invoice.invoice_number ??
                                                `#${invoice.id}`
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{
                                                invoice.organization?.name ??
                                                '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ invoice.project?.code ?? '—' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ formatDate(invoice.due_date) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge
                                                :variant="
                                                    invoiceStatusVariant(
                                                        invoice.status,
                                                    )
                                                "
                                            >
                                                {{
                                                    invoiceStatusLabel(
                                                        invoice.status,
                                                    )
                                                }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <FileLink
                                                v-if="
                                                    invoice.attachments?.length
                                                "
                                                :href="
                                                    invoice.attachments[0]
                                                        .download_url
                                                "
                                                :label="
                                                    invoice.attachments[0]
                                                        .original_filename
                                                "
                                                show-icon
                                                compact
                                            />
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                            >
                                                —
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-end font-semibold tabular-nums"
                                        >
                                            {{ formatCurrency(invoice.total, invoice.currency) }}
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <RowActionsMenu
                                                :actions="
                                                    invoiceActions(invoice)
                                                "
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t px-4 py-3">
                            <MisPagination :pagination="props.invoices" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </V2ListPage>
</template>
