<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Wallet } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MisPagination from '@/components/MisPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';
import type { Paginated } from '@/lib/format';

interface PayrollRun {
    id: number;
    period_year: number;
    period_month: number;
    status: string;
    processed_by?: { name: string } | null;
    items_count?: number;
}

interface Props {
    payrollRuns: Paginated<PayrollRun>;
}

defineProps<Props>();

const { t } = useMisPage();

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
</script>

<template>
    <Head :title="t('Payroll')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                :title="t('Payroll')"
                :description="t('Monthly payroll runs and disbursements')"
            />
            <Form
                action="/hr/payroll"
                method="post"
                class="flex flex-wrap items-end gap-3"
                :options="{ preserveScroll: true, forceFormData: true }"
                v-slot="{ processing }"
            >
                <div class="grid gap-2">
                    <label class="text-sm font-medium" for="period_year">{{
                        t('Year')
                    }}</label>
                    <input
                        id="period_year"
                        name="period_year"
                        type="number"
                        :default-value="new Date().getFullYear()"
                        class="flex h-9 w-28 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-medium" for="period_month">{{
                        t('Month')
                    }}</label>
                    <input
                        id="period_month"
                        name="period_month"
                        type="number"
                        min="1"
                        max="12"
                        :default-value="new Date().getMonth() + 1"
                        class="flex h-9 w-20 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        required
                    />
                </div>
                <Button type="submit" :disabled="processing">{{
                    t('New run')
                }}</Button>
            </Form>
        </div>

        <div
            v-if="payrollRuns.data.length === 0"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('No payroll runs recorded.') }}
        </div>

        <div v-else class="space-y-4">
            <Card v-for="run in payrollRuns.data" :key="run.id">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Wallet class="size-5" />
                                <Link
                                    :href="`/hr/payroll/${run.id}`"
                                    class="hover:underline"
                                >
                                    {{ monthName(run.period_month) }}
                                    {{ run.period_year }}
                                </Link>
                            </CardTitle>
                            <CardDescription>
                                {{ t('Processed by') }}
                                {{ run.processed_by?.name ?? '—' }} ·
                                {{
                                    t(':count line items', {
                                        count: String(run.items_count ?? 0),
                                    })
                                }}
                            </CardDescription>
                        </div>
                        <Badge>{{ run.status }}</Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/hr/payroll/${run.id}`">
                            {{ t('View run details') }}
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <MisPagination :pagination="payrollRuns" />
        </div>
    </div>
</template>
