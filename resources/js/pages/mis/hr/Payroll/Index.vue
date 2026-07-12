<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ClipboardList, Plus, Printer, Wallet } from '@lucide/vue';
import Can from '@/components/Can.vue';
import MisPage from '@/components/MisPage.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMisPage } from '@/composables/useMisPage';
import { formatCurrency, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { cn } from '@/lib/utils';

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface PayrollRun {
    id: number;
    title: string;
    payroll_type: 'general' | 'project';
    project?: { id: number; code: string; name?: string } | null;
    date_from: string;
    date_to: string;
    period_year: number;
    period_month: number;
    status: string;
    items_count?: number;
    total_net?: number | string | null;
    processed_by?: { name: string } | null;
    created_by_name?: string | null;
}

interface Props {
    payrollRuns: Paginated<PayrollRun>;
    projects: ProjectOption[];
    filters?: {
        date_from?: string;
        date_to?: string;
        year?: number;
        month?: number;
        project_id?: number;
    };
}

const props = defineProps<Props>();

const { t, viewAction, deleteAction } = useMisPage();

const newPayrollType = ref<'general' | 'project'>('general');
const newProjectId = ref<string>('');

watch(newPayrollType, (type) => {
    if (type === 'general') {
        newProjectId.value = '';
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
        ],
    },
});

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );

const typeLabel = (type: PayrollRun['payroll_type']): string =>
    type === 'project' ? t('Project') : t('General');

const typeVariant = (type: PayrollRun['payroll_type']): 'default' | 'secondary' =>
    type === 'project' ? 'default' : 'secondary';

const listTitle = computed(() => {
    if (props.filters?.year && props.filters?.month) {
        const month = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
            new Date(2000, props.filters.month - 1, 1),
        );

        return t('Payroll runs for :month :year', {
            month,
            year: String(props.filters.year),
        });
    }

    return t('All payroll runs');
});

const runStats = computed(() => [
    {
        label: t('Total runs'),
        value: formatNumber(props.payrollRuns.meta?.total ?? props.payrollRuns.data.length),
        icon: Wallet,
        accent: 'bg-primary/10 text-primary',
    },
    {
        label: t('On this page'),
        value: formatNumber(props.payrollRuns.data.length),
        icon: ClipboardList,
        accent: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
]);

const printUrl = (run: PayrollRun): string =>
    `/hr/payroll/${run.id}/print`;

const payrollActions = (run: PayrollRun): RowActionItem[] => [
    viewAction(`/hr/payroll/${run.id}`),
    {
        label: t('Print'),
        icon: Printer,
        href: `${printUrl(run)}?autoprint=1`,
        download: true,
    },
    deleteAction(
        {
            href: `/hr/payroll/${run.id}`,
            title: t('Delete payroll run?'),
            description: t(
                'This payroll run and all its line items will be removed.',
            ),
        },
        'hr.delete',
    ),
];
</script>

<template>
    <Head :title="t('Payroll')" />

    <MisPage>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Wallet class="size-5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ t('Payroll') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Generate payroll from attendance in one click.') }}
                    </p>
                </div>
            </div>
            <Can permission="hr.create">
                <Form
                    action="/hr/payroll"
                    method="post"
                    class="flex flex-wrap items-end gap-2 rounded-xl border bg-card p-3 shadow-sm"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing }"
                >
                    <div class="grid gap-1.5">
                        <Label for="payroll_type" class="text-xs font-medium">
                            {{ t('Type') }}
                        </Label>
                        <select
                            id="payroll_type"
                            v-model="newPayrollType"
                            name="payroll_type"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                        >
                            <option value="general">{{ t('General') }}</option>
                            <option value="project">{{ t('Project') }}</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="title" class="text-xs font-medium">
                            {{ t('Title') }}
                        </Label>
                        <Input
                            id="title"
                            name="title"
                            type="text"
                            class="h-9 w-40"
                            :placeholder="t('Optional')"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date_from" class="text-xs font-medium">
                            {{ t('From') }}
                        </Label>
                        <Input
                            id="date_from"
                            name="date_from"
                            type="date"
                            required
                            class="h-9 w-36"
                            :default-value="filters?.date_from"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date_to" class="text-xs font-medium">
                            {{ t('To') }}
                        </Label>
                        <Input
                            id="date_to"
                            name="date_to"
                            type="date"
                            required
                            class="h-9 w-36"
                            :default-value="filters?.date_to"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="project_id" class="text-xs font-medium">
                            {{ t('Project') }}
                        </Label>
                        <select
                            id="project_id"
                            v-model="newProjectId"
                            name="project_id"
                            :disabled="newPayrollType !== 'project'"
                            :required="newPayrollType === 'project'"
                            class="h-9 min-w-[9rem] rounded-md border border-input bg-background px-3 text-sm shadow-xs disabled:opacity-50"
                        >
                            <option value="">{{ t('Select project') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                            >
                                {{ project.code }}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" class="h-9 gap-1.5 shadow-sm" :disabled="processing">
                        <Plus class="size-3.5" />
                        {{ t('Generate payroll') }}
                    </Button>
                </Form>
            </Can>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div
                v-for="stat in runStats"
                :key="stat.label"
                class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm"
            >
                <div
                    :class="
                        cn(
                            'flex size-9 items-center justify-center rounded-lg',
                            stat.accent,
                        )
                    "
                >
                    <component :is="stat.icon" class="size-4" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
                    <p class="text-2xl font-bold tabular-nums tracking-tight">
                        {{ stat.value }}
                    </p>
                </div>
            </div>
        </div>

        <Card class="overflow-hidden border-border/60 shadow-sm">
            <CardHeader class="border-b bg-muted/10 pb-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <CardTitle class="text-base font-semibold">
                        {{ listTitle }}
                    </CardTitle>
                    <form
                        method="get"
                        action="/hr/payroll"
                        class="flex flex-wrap items-end gap-2"
                    >
                        <Input
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            :default-value="filters?.year ?? ''"
                            class="h-9 w-24"
                            :placeholder="t('Year')"
                        />
                        <Input
                            name="month"
                            type="number"
                            min="1"
                            max="12"
                            :default-value="filters?.month ?? ''"
                            class="h-9 w-20"
                            :placeholder="t('Month')"
                        />
                        <select
                            name="project_id"
                            class="h-9 min-w-[8rem] rounded-md border border-input bg-background px-3 text-sm shadow-xs"
                        >
                            <option value="">{{ t('All projects') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                                :selected="filters?.project_id === project.id"
                            >
                                {{ project.code }}
                            </option>
                        </select>
                        <Button type="submit" variant="outline" class="h-9 shadow-sm">
                            {{ t('Filter') }}
                        </Button>
                    </form>
                </div>
            </CardHeader>
            <CardContent class="space-y-4 pt-4">
                <div
                    v-if="payrollRuns.data.length === 0"
                    class="rounded-xl border border-dashed bg-muted/10 px-4 py-12 text-center text-sm text-muted-foreground"
                >
                    {{ t('No payroll runs yet. Record attendance first, then generate payroll.') }}
                </div>

                <div v-else class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-start text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="px-4 py-3 font-semibold">{{ t('Payroll') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Type') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Date range') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Project') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Staff') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ t('Total net') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="run in payrollRuns.data"
                                :key="run.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/20"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        :href="`/hr/payroll/${run.id}`"
                                        class="font-medium hover:text-primary hover:underline"
                                    >
                                        {{ run.title }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="typeVariant(run.payroll_type)">
                                        {{ typeLabel(run.payroll_type) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground tabular-nums">
                                    {{ formatDate(run.date_from) }}
                                    —
                                    {{ formatDate(run.date_to) }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ run.project?.code ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-w-[2rem] justify-center rounded-md bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums">
                                        {{ run.items_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium tabular-nums">
                                    {{ formatCurrency(Number(run.total_net ?? 0)) }}
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <RowActionsMenu :actions="payrollActions(run)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="payrollRuns" />
            </CardContent>
        </Card>
    </MisPage>
</template>
