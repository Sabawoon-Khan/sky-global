<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Receipt, Wallet } from '@lucide/vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
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
import { formatCurrency, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

interface PayrollRun {
    id: number;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items_count?: number;
    total_net?: number | string | null;
}

interface Props {
    payrollRuns: Paginated<PayrollRun>;
    filters?: {
        year?: number;
    };
}

defineProps<Props>();

const { t, viewAction, deleteAction } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Payroll', href: '/hr/payroll' },
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

const periodLabel = (run: PayrollRun): string =>
    `${monthName(run.period_month)} ${run.period_year}`;

const statusVariant = (status: string): 'default' | 'secondary' | 'outline' =>
    status === 'processed' ? 'default' : 'secondary';

const payrollActions = (run: PayrollRun): RowActionItem[] => [
    viewAction(`/hr/payroll/${run.id}`),
    deleteAction(
        {
            href: `/hr/payroll/${run.id}`,
            title: t('Delete payroll run?'),
            description: t(
                'This payroll run and all its line items will be removed. Applied adjustments for this period will become pending again.',
            ),
        },
        'hr.delete',
    ),
];
</script>

<template>
    <Head :title="t('Payroll')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="grid gap-6 xl:grid-cols-3">
            <Can permission="hr.create">
                <Card class="xl:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Plus class="size-5" />
                            {{ t('Create payroll run') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action="/hr/payroll"
                            method="post"
                            class="grid gap-4"
                            :options="{
                                preserveScroll: true,
                                resetOnSuccess: true,
                            }"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="period_year">{{
                                        t('Year')
                                    }}</Label>
                                    <Input
                                        id="period_year"
                                        name="period_year"
                                        type="number"
                                        min="2000"
                                        max="2100"
                                        required
                                        :default-value="
                                            filters?.year ??
                                            new Date().getFullYear()
                                        "
                                    />
                                    <InputError :message="errors.period_year" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="period_month">{{
                                        t('Month')
                                    }}</Label>
                                    <Input
                                        id="period_month"
                                        name="period_month"
                                        type="number"
                                        min="1"
                                        max="12"
                                        required
                                        :default-value="
                                            new Date().getMonth() + 1
                                        "
                                    />
                                    <InputError :message="errors.period_month" />
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'Record monthly attendance, approve records, then process payroll',
                                    )
                                }}
                            </p>

                            <Button type="submit" :disabled="processing">
                                {{ t('New run') }}
                            </Button>
                        </Form>

                        <div class="mt-4 border-t pt-4">
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full"
                                as-child
                            >
                                <Link href="/hr/payroll-adjustments">
                                    <Receipt class="size-4" />
                                    {{ t('Payroll Adjustments') }}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </Can>

            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Wallet class="size-5" />
                        {{ t('Monthly payroll runs') }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form
                        method="get"
                        action="/hr/payroll"
                        class="flex flex-wrap items-end gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="filter_year">{{ t('Year') }}</Label>
                            <Input
                                id="filter_year"
                                name="year"
                                type="number"
                                :default-value="
                                    filters?.year ?? new Date().getFullYear()
                                "
                                class="w-28"
                            />
                        </div>
                        <Button type="submit" variant="outline">{{
                            t('Filter')
                        }}</Button>
                    </form>

                    <div
                        v-if="payrollRuns.data.length === 0"
                        class="ui-empty-state"
                    >
                        {{ t('No payroll runs recorded.') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-start text-muted-foreground"
                                >
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Period') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Status') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Line items') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Total net pay') }}
                                    </th>
                                    <th class="pe-4 pb-3 font-medium">
                                        {{ t('Processed by') }}
                                    </th>
                                    <th class="pb-3 text-end font-medium">
                                        {{ t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="run in payrollRuns.data"
                                    :key="run.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pe-4 font-medium">
                                        <Link
                                            :href="`/hr/payroll/${run.id}`"
                                            class="hover:underline"
                                        >
                                            {{ periodLabel(run) }}
                                        </Link>
                                    </td>
                                    <td class="py-3 pe-4">
                                        <Badge :variant="statusVariant(run.status)">
                                            {{ run.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pe-4 text-muted-foreground">
                                        {{ run.items_count ?? 0 }}
                                    </td>
                                    <td class="py-3 pe-4 font-medium">
                                        {{
                                            run.status === 'processed'
                                                ? formatCurrency(
                                                      Number(run.total_net ?? 0),
                                                  )
                                                : '—'
                                        }}
                                    </td>
                                    <td class="py-3 pe-4 text-muted-foreground">
                                        {{
                                            run.status === 'processed'
                                                ? (run.processed_by?.name ?? '—')
                                                : '—'
                                        }}
                                    </td>
                                    <td class="py-3 text-end">
                                        <RowActionsMenu
                                            :actions="payrollActions(run)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <MisPagination :pagination="payrollRuns" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
