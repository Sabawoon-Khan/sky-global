<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    AlertCircle,
    CalendarDays,
    CheckCircle2,
    Users,
    Wallet,
} from '@lucide/vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Personnel {
    first_name?: string;
    last_name?: string;
}

interface PayrollItem {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel?: Personnel | null;
    project?: { id: number; code: string; name?: string } | null;
    base_amount: number;
    deductions: number;
    net_amount: number;
    currency?: string | null;
    notes?: string | null;
}

interface PayrollRun {
    id: number;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items?: PayrollItem[];
    attachments?: EntityAttachment[];
}

interface Props {
    payrollRun: PayrollRun;
    approvedAttendanceCount: number;
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
            { title: 'Run details', href: '#' },
        ],
    },
});

const monthName = (month: number): string => {
    if (!month || month < 1 || month > 12) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );
};

const formatCurrency = (value?: number | null, currency = 'USD'): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
};

const personnelLabel = (item: PayrollItem): string => {
    if (item.personnel?.first_name || item.personnel?.last_name) {
        return [item.personnel.first_name, item.personnel.last_name]
            .filter(Boolean)
            .join(' ');
    }

    return `#${item.personnel_id}`;
};

const personnelTypeLabel = (type: string): string => {
    const parts = type.split('\\');

    return parts[parts.length - 1] ?? type;
};

const periodLabel = computed(
    () =>
        `${monthName(props.payrollRun.period_month)} ${props.payrollRun.period_year}`,
);

const itemCount = computed(() => props.payrollRun.items?.length ?? 0);

const totalNet = computed(() =>
    (props.payrollRun.items ?? []).reduce(
        (sum, item) => sum + Number(item.net_amount),
        0,
    ),
);

const isDraft = computed(() => props.payrollRun.status !== 'processed');

const isProcessed = computed(() => props.payrollRun.status === 'processed');

const attendanceFilterUrl = computed(
    () =>
        `/hr/attendance?year=${props.payrollRun.period_year}&month=${props.payrollRun.period_month}`,
);
</script>

<template>
    <Head :title="`${periodLabel} Payroll`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="periodLabel"
                    description="Review payroll line items generated from approved attendance"
                />
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <Badge :variant="isProcessed ? 'default' : 'secondary'">
                        {{ payrollRun.status }}
                    </Badge>
                    <span v-if="isProcessed" class="text-sm text-muted-foreground">
                        Processed by {{ payrollRun.processed_by?.name ?? '—' }}
                    </span>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/hr/payroll">Back to list</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="attendanceFilterUrl">View attendance</Link>
                </Button>
                <Form
                    v-if="isDraft"
                    :action="`/hr/payroll/${payrollRun.id}/process`"
                    method="post"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Process payroll
                    </Button>
                </Form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Approved attendance</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <CalendarDays class="size-5 text-muted-foreground" />
                        {{ approvedAttendanceCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Records for {{ periodLabel }} ready for payroll
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Line items</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Users class="size-5 text-muted-foreground" />
                        {{ itemCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Personnel entries in this payroll run
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Total net pay</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-2xl">
                        <Wallet class="size-5 text-muted-foreground" />
                        {{ formatCurrency(totalNet) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Combined net amount for this run
                </CardContent>
            </Card>
        </div>

        <Card
            v-if="isDraft"
            class="border-dashed bg-muted/20"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <AlertCircle class="size-5" />
                    Next step
                </CardTitle>
                <CardDescription>
                    This run is still a draft. Processing will create one line item
                    per approved attendance record for {{ periodLabel }}.
                </CardDescription>
            </CardHeader>
            <CardContent class="text-sm text-muted-foreground">
                <p>
                    You currently have
                    <strong class="text-foreground">{{ approvedAttendanceCount }}</strong>
                    approved attendance
                    {{ approvedAttendanceCount === 1 ? 'record' : 'records' }}.
                    Click <strong class="text-foreground">Process payroll</strong>
                    when you are ready to calculate amounts.
                </p>
            </CardContent>
        </Card>

        <Card
            v-else-if="itemCount === 0"
            class="border-dashed bg-muted/20"
        >
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <AlertCircle class="size-5" />
                    No line items generated
                </CardTitle>
                <CardDescription>
                    This run was processed, but no approved attendance existed for
                    {{ periodLabel }}.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground">
                <p>
                    Record attendance, approve it, then create a new payroll run or
                    re-process if your workflow allows it.
                </p>
                <Button variant="outline" size="sm" class="w-fit" as-child>
                    <Link :href="attendanceFilterUrl">Go to attendance</Link>
                </Button>
            </CardContent>
        </Card>

        <Card v-else-if="isProcessed">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base text-green-700 dark:text-green-400">
                    <CheckCircle2 class="size-5" />
                    Payroll processed
                </CardTitle>
                <CardDescription>
                    {{ itemCount }} line items · Total net {{ formatCurrency(totalNet) }}
                </CardDescription>
            </CardHeader>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Wallet class="size-5" />
                    Line items
                </CardTitle>
                <CardDescription>
                    Amounts calculated from approved attendance and salary rates
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="itemCount === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{
                        isDraft
                            ? 'Line items will appear here after you process this run.'
                            : 'No payroll amounts were calculated for this period.'
                    }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Personnel</th>
                                <th class="pb-3 pr-4 font-medium">Type</th>
                                <th class="pb-3 pr-4 font-medium">Project</th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    Base
                                </th>
                                <th class="pb-3 pr-4 text-right font-medium">
                                    Deductions
                                </th>
                                <th class="pb-3 text-right font-medium">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in payrollRun.items"
                                :key="item.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <div>{{ personnelLabel(item) }}</div>
                                    <p
                                        v-if="item.notes"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ item.notes }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ personnelTypeLabel(item.personnel_type) }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ item.project?.code ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    {{
                                        formatCurrency(
                                            item.base_amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3 pr-4 text-right text-destructive">
                                    {{
                                        formatCurrency(
                                            item.deductions,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3 text-right font-medium">
                                    {{
                                        formatCurrency(
                                            item.net_amount,
                                            item.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <EntityAttachments
            v-if="payrollRun.attachments?.length"
            :attachments="payrollRun.attachments"
        />
    </div>
</template>
