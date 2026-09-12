<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { personnelStatusActions } from '@/lib/status-actions';
import {
    Plus,
    UserCheck,
    UserMinus,
    UserX,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string | null;
    email?: string | null;
    status: string;
    is_permanent?: boolean;
    job_detail?: {
        designation?: string | null;
        department?: { name: string } | null;
    } | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    employees: Paginated<Employee>;
    stats: {
        total: number;
        active: number;
        inactive: number;
        terminated: number;
        by_status?: Record<string, number>;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: { search?: string; status?: string };
}>();

const onlyKeys = ['employees', 'stats', 'chart', 'filters'];

const { filters, pending, apply, clear } = useMisFilters(
    '/hr/employees',
    { search: props.filters?.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.employees.data);
const { t, viewAction, editAction, gateActions, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'designation', label: t('Designation') },
    { key: 'department', label: t('Department') },
    { key: 'contact', label: t('Contact') },
    { key: 'type', label: t('Type') },
    { key: 'status', label: t('Status') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const statusPalette = [
    'var(--school-navy)',
    'var(--brand-accent)',
    'var(--school-gold)',
    'var(--muted-foreground)',
    '#3d5a80',
    '#8b9bb4',
];

const pipeline = computed(() => {
    const rows = (props.chart?.status ?? []).filter((row) => row.value > 0);
    const total = Math.max(
        rows.reduce((sum, row) => sum + row.value, 0),
        props.stats.total,
        1,
    );
    const activeShare =
        total > 0 ? Math.round((props.stats.active / total) * 100) : 0;

    return {
        activeShare,
        segments: rows.map((row, index) => ({
            key: row.key,
            label: row.label,
            value: row.value,
            color: statusPalette[index % statusPalette.length],
            width: Math.max(row.value > 0 ? 6 : 0, (row.value / total) * 100),
        })),
    };
});

const monthlyBars = computed(() => {
    const rows =
        props.chart?.monthly?.length > 0
            ? props.chart.monthly
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  value: 0,
              }));
    const max = Math.max(...rows.map((row) => Number(row.value) || 0), 1);

    return rows.map((row) => {
        const value = Number(row.value) || 0;
        return {
            key: row.key,
            label: row.label,
            value,
            height: Math.max(value > 0 ? 6 : 3, Math.round((value / max) * 44)),
            peak: value === max && value > 0,
        };
    });
});

const fullName = (employee: Employee): string =>
    `${employee.first_name} ${employee.last_name}`;

const employeeActions = (employee: Employee): RowActionItem[] => [
    viewAction(`/hr/employees/${employee.id}`),
    editAction(`/hr/employees/${employee.id}/edit`, 'hr.edit'),
    ...gateActions(
        personnelStatusActions({
            url: `/hr/employees/${employee.id}`,
            name: fullName(employee),
            status: employee.status,
            t,
        }),
        'hr.edit',
    ),
];
</script>

<template>
    <Head :title="t('Employees')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('HR') }}</template>
            <template #title>{{ t('Employees') }}</template>
            <template #description>
                {{ t('Workforce roster — permanent and project-based staff.') }}
            </template>
            <template #side>
                <Link
                    v-if="can('hr.create')"
                    href="/hr/employees/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Add Employee') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Workforce') }}</template>
                        <template #meta
                            >{{ pipeline.activeShare }}%
                            {{ t('Active') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.active)
                                }}</strong>
                                <small>{{ t('Active') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in pipeline.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in pipeline.segments.slice(0, 4)"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ formatNumber(seg.value) }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Created by month') }}</template>
                        <template #meta
                            >{{ formatNumber(stats.total) }}
                            {{ t('Total') }}</template
                        >

                        <div class="money-chart">
                            <div
                                v-for="bar in monthlyBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`"
                            >
                                <div class="money-pair">
                                    <i
                                        class="usd"
                                        :style="{ height: `${bar.height}px` }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Employees')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Active')"
                        :value="formatNumber(stats.active)"
                    >
                        <template #icon><UserCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Inactive')"
                        :value="formatNumber(stats.inactive)"
                    >
                        <template #icon><UserMinus /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Terminated')"
                        :value="formatNumber(stats.terminated)"
                    >
                        <template #icon><UserX /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="hr-employees"
            :columns="tableColumns"
            :pending="pending && employees.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search employees...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <th>{{ t('Name') }}</th>
                            <th>{{ t('Designation') }}</th>
                            <th>{{ t('Department') }}</th>
                            <th>{{ t('Contact') }}</th>
                            <th>{{ t('Type') }}</th>
                            <th>{{ t('Status') }}</th>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(employee, index) in sortedRows"
                            :key="employee.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="employees.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/hr/employees/${employee.id}`"
                                    class="code-chip"
                                >
                                    {{ fullName(employee) }}
                                </Link>
                            </td>
                            <td class="muted">
                                {{ employee.job_detail?.designation ?? '—' }}
                            </td>
                            <td class="muted">
                                {{
                                    employee.job_detail?.department?.name ?? '—'
                                }}
                            </td>
                            <td class="muted">
                                <div>{{ employee.phone ?? '—' }}</div>
                                <div v-if="employee.email" class="text-xs">
                                    {{ employee.email }}
                                </div>
                            </td>
                            <td>
                                {{
                                    employee.is_permanent
                                        ? t('Permanent')
                                        : t('Project-based')
                                }}
                            </td>
                            <td>
                                <StatusBadge :status="employee.status" />
                            </td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="employeeActions(employee)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!employees.data.length"
                            :colspan="visibleColCount"
                            :title="t('No employees found.')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('hr.create')"
                                    href="/hr/employees/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Add Employee') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="employees.links?.length" #pager>
                <V2Pager :items="employees" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
