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
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { invoiceStatusActions } from '@/lib/status-actions';
import { cn } from '@/lib/utils';
import { ChevronDown, FileText, Paperclip, Plus } from '@lucide/vue';

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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            { title: 'Invoices', href: '/finance/invoices' },
        ],
    },
});

const showInvoiceForm = ref(false);
const invoiceSubtotal = ref('');
const invoiceTax = ref('');
const invoiceTotal = computed(() => {
    const subtotal = Number(invoiceSubtotal.value) || 0;
    const tax = Number(invoiceTax.value) || 0;

    return (subtotal + tax).toFixed(2);
});

watch(showInvoiceForm, (open) => {
    if (!open) {
        invoiceSubtotal.value = '';
        invoiceTax.value = '';
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
            <template #description>
                {{ t('All invoice amounts are in Afghani (AFN).') }}
            </template>
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
                    <p class="text-sm text-muted-foreground">
                        {{ t('All invoice amounts are in Afghani (AFN).') }}
                    </p>
                </div>
                <Can permission="finance.create">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showInvoiceForm = !showInvoiceForm"
                    >
                        <Plus class="me-1 size-4" />
                        {{ showInvoiceForm ? t('Close') : t('Add invoice') }}
                        <ChevronDown
                            class="ms-1 size-4 transition-transform"
                            :class="cn(showInvoiceForm && 'rotate-180')"
                        />
                    </Button>
                </Can>
            </div>

            <Can permission="finance.create">
                <Card v-if="showInvoiceForm">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">{{
                            t('New invoice')
                        }}</CardTitle>
                        <CardDescription>
                            {{
                                t(
                                    'Create an invoice in AFN for a client or project.',
                                )
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action="/finance/invoices"
                            method="post"
                            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            :options="{
                                preserveScroll: true,
                                resetOnSuccess: true,
                                forceFormData: true,
                            }"
                            validate-files
                            v-slot="{ errors, processing }"
                            @success="showInvoiceForm = false"
                        >
                            <input type="hidden" name="currency" value="AFN" />

                            <div class="grid gap-2">
                                <Label for="inv-number"
                                    >{{ t('Invoice #') }} *</Label
                                >
                                <Input
                                    id="inv-number"
                                    name="invoice_number"
                                    required
                                />
                                <InputError
                                    :message="errors.invoice_number"
                                />
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
                                <Label for="inv-subtotal"
                                    >{{ t('Subtotal') }} (AFN) *</Label
                                >
                                <Input
                                    id="inv-subtotal"
                                    name="subtotal"
                                    type="number"
                                    min="0"
                                    step="1"
                                    required
                                    v-model="invoiceSubtotal"
                                />
                                <InputError :message="errors.subtotal" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-tax"
                                    >{{ t('Tax') }} (AFN)</Label
                                >
                                <Input
                                    id="inv-tax"
                                    name="tax"
                                    type="number"
                                    min="0"
                                    step="1"
                                    v-model="invoiceTax"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="inv-total"
                                    >{{ t('Total') }} (AFN)</Label
                                >
                                <div
                                    class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm font-semibold tabular-nums"
                                >
                                    {{ formatAfn(Number(invoiceTotal) || 0) }}
                                </div>
                                <input
                                    type="hidden"
                                    name="total"
                                    :value="invoiceTotal"
                                />
                                <InputError :message="errors.total" />
                            </div>
                            <div
                                class="grid gap-2 sm:col-span-2 lg:col-span-3"
                            >
                                <OptionalAttachmentField
                                    :label="t('Attachment')"
                                    :error="errors.attachment"
                                />
                            </div>
                            <div
                                class="flex items-center gap-2 sm:col-span-2 lg:col-span-3"
                            >
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="processing"
                                >
                                    {{ t('Save invoice') }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="showInvoiceForm = false"
                                >
                                    {{ t('Cancel') }}
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </Can>

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
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Invoice #') }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Client') }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Project') }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Due Date') }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Status') }}
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            {{ t('Attachment') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('Amount') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-end font-medium"
                                        >
                                            {{ t('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="invoice in props.invoices.data"
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
                                            {{ formatAfn(invoice.total) }}
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
