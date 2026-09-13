<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
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
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import SortableTh from '@/components/SortableTh.vue';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatCurrency, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { invoiceStatusActions } from '@/lib/status-actions';
import { FileText, Paperclip, Plus, Printer, Trash2 } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
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
    project?: { id: number; code: string; name: string } | null;
    organization?: { id: number; name: string } | null;
    attachments?: FinanceAttachment[];
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
}>();

const { t, deleteAction, gateActions } = useMisPage();

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
const invoiceTax = ref('');
const invoiceLines = ref([
    { description: '', quantity: '1', unit_price: '', days: '1' },
]);

const invoiceSubtotal = computed(() =>
    invoiceLines.value.reduce((sum, line) => {
        const quantity = Number(line.quantity) || 0;
        const unitPrice = Number(line.unit_price) || 0;
        const days = Number(line.days) || 1;

        return sum + quantity * unitPrice * days;
    }, 0),
);

const invoiceTotal = computed(
    () => invoiceSubtotal.value + (Number(invoiceTax.value) || 0),
);

const addInvoiceLine = (): void => {
    invoiceLines.value.push({
        description: '',
        quantity: '1',
        unit_price: '',
        days: '1',
    });
};

const removeInvoiceLine = (index: number): void => {
    if (invoiceLines.value.length === 1) {
        invoiceLines.value[0] = {
            description: '',
            quantity: '1',
            unit_price: '',
            days: '1',
        };
        return;
    }

    invoiceLines.value.splice(index, 1);
};

watch(showInvoiceForm, (open) => {
    if (!open) {
        invoiceTax.value = '';
        invoiceLines.value = [
            { description: '', quantity: '1', unit_price: '', days: '1' },
        ];
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
                        @click="showInvoiceForm = true"
                    >
                        <Plus class="me-1 size-4" />
                        {{ t('Add invoice') }}
                    </Button>
                </Can>
            </div>

            <Dialog
                :open="showInvoiceForm"
                @update:open="showInvoiceForm = $event"
            >
                <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <Form
                        action="/finance/invoices"
                        method="post"
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
                            <DialogTitle>{{ t('New invoice') }}</DialogTitle>
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

                            <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="inv-status">{{
                                    t('Status')
                                }}</Label>
                                <select
                                    id="inv-status"
                                    name="status"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="draft">
                                        {{ t('Draft') }}
                                    </option>
                                    <option value="sent">
                                        {{ t('Sent') }}
                                    </option>
                                    <option value="paid">
                                        {{ t('Paid') }}
                                    </option>
                                    <option value="overdue">
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
                                />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="inv-services">{{
                                    t('Services')
                                }}</Label>
                                <Input id="inv-services" name="services" />
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
                                    {{ formatCurrency(invoiceTotal, 'USD') }}
                                </div>
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="inv-notes">{{ t('Notes') }}</Label>
                                <Input id="inv-notes" name="notes" />
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
                                            <a
                                                v-if="
                                                    invoice.attachments?.length
                                                "
                                                :href="
                                                    invoice.attachments[0]
                                                        .download_url
                                                "
                                                class="inline-flex items-center gap-1 text-primary hover:underline"
                                                :title="
                                                    invoice.attachments[0]
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
                                                        invoice.attachments[0]
                                                            .original_filename
                                                    }}
                                                </span>
                                            </a>
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
