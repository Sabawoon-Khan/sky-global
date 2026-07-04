<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CalendarDays, ChevronRight, Clock, FileCheck2 } from '@lucide/vue';
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

interface MonthRow {
    month: number;
    month_name: string;
    total: number;
    recorded: number;
    approved: number;
    draft: number;
    missing: number;
    has_records: boolean;
}

interface ProjectOption {
    id: number;
    code: string;
    name: string;
}

interface Summary {
    year: number;
    months_with_records: number;
    total_records: number;
    missing_this_month: number;
}

interface Props {
    months: MonthRow[];
    projects: ProjectOption[];
    summary: Summary;
    filters?: {
        year?: number;
        project_id?: number;
    };
}

const props = defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
        ],
    },
});

const filterYear = computed(
    () => props.filters?.year ?? new Date().getFullYear(),
);

const monthHref = (month: number): string => {
    const params = new URLSearchParams({
        year: String(filterYear.value),
        month: String(month),
    });

    if (props.filters?.project_id) {
        params.set('project_id', String(props.filters.project_id));
    }

    return `/hr/attendance/create?${params.toString()}`;
};

const monthStatus = (
    row: MonthRow,
): { label: string; variant: 'default' | 'secondary' | 'outline' } => {
    if (!row.has_records) {
        return { label: t('Not started'), variant: 'outline' };
    }

    if (row.missing > 0) {
        return { label: t('Incomplete'), variant: 'secondary' };
    }

    if (row.approved === row.total && row.total > 0) {
        return { label: t('Approved'), variant: 'default' };
    }

    return { label: t('Recorded'), variant: 'secondary' };
};
</script>

<template>
    <Head :title="t('Attendance')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ t('Attendance') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'Select a month to record or update attendance for all staff.',
                    )
                }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ t('Year') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">{{ summary.year }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            t(':count months with records', {
                                count: String(summary.months_with_records),
                            })
                        }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <FileCheck2 class="size-4" />
                        {{ t('Total records') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ summary.total_records }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <Clock class="size-4" />
                        {{ t('Missing this month') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ summary.missing_this_month }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CalendarDays class="size-5" />
                    {{ t('Monthly attendance') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/hr/attendance"
                    class="flex flex-wrap items-end gap-4 rounded-lg border bg-muted/20 p-4"
                >
                    <div class="grid gap-2">
                        <Label for="filter_year">{{ t('Year') }}</Label>
                        <Input
                            id="filter_year"
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            :default-value="filterYear"
                            class="w-28"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="filter_project">{{ t('Project') }}</Label>
                        <select
                            id="filter_project"
                            name="project_id"
                            class="flex h-9 min-w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">{{ t('All projects') }}</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                                :selected="
                                    filters?.project_id === project.id
                                "
                            >
                                {{ project.code }} — {{ project.name }}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {{ t('Filter') }}
                    </Button>
                </form>

                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b bg-muted/30 text-start text-muted-foreground"
                            >
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Month') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Status') }}
                                </th>
                                <th class="px-4 py-3 text-center font-medium">
                                    {{ t('Recorded') }}
                                </th>
                                <th class="px-4 py-3 text-center font-medium">
                                    {{ t('Approved') }}
                                </th>
                                <th class="px-4 py-3 text-center font-medium">
                                    {{ t('Missing') }}
                                </th>
                                <th class="px-4 py-3 text-end font-medium">
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in months"
                                :key="row.month"
                                class="border-b last:border-0 transition-colors hover:bg-muted/20"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ row.month_name }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="monthStatus(row).variant">
                                        {{ monthStatus(row).label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    {{ row.recorded }}
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    {{ row.approved }}
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    <span
                                        :class="
                                            row.missing > 0
                                                ? 'font-medium text-amber-600 dark:text-amber-400'
                                                : ''
                                        "
                                    >
                                        {{ row.missing }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="monthHref(row.month)">
                                            {{
                                                row.has_records
                                                    ? t('Update')
                                                    : t('Record')
                                            }}
                                            <ChevronRight class="size-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
