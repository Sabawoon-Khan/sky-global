<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatDate, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import {
    Award,
    Building2,
    GraduationCap,
    Plus,
    Shield,
    UserPlus,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface TrainingGuard {
    id: number;
    name: string;
    father_name: string;
    grandfather_name?: string | null;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    batch_number: string;
    status: string;
    training_path?: string | null;
    employee_id?: number | null;
    contractor_id?: number | null;
    employee?: { id: number; name: string } | null;
    contractor?: { id: number; name: string } | null;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    guards: Paginated<TrainingGuard>;
    stats: {
        total: number;
        registered: number;
        ministry: number;
        company: number;
        certified: number;
        by_status?: Record<string, number>;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    batches?: string[];
    filters?: {
        search?: string | null;
        status?: string | null;
        batch_number?: string | null;
        training_path?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const onlyKeys = ['guards', 'stats', 'chart', 'filters', 'batches'];

const { filters, pending, apply, clear } = useMisFilters(
    '/training/guards',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
        batch_number: props.filters?.batch_number ?? '',
        training_path: props.filters?.training_path ?? '',
        date_from: props.filters?.date_from ?? '',
        date_to: props.filters?.date_to ?? '',
    },
    {
        search: '',
        status: '',
        batch_number: '',
        training_path: '',
        date_from: '',
        date_to: '',
    },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.guards.data, {
    accessors: {
        name: (row) => row.name,
        father: (row) => row.father_name,
        batch: (row) => row.batch_number,
        period: (row) => row.start_date,
        path: (row) => row.training_path,
        status: (row) => row.status,
    },
});
const { t, viewAction, editAction, deleteAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Guards', href: '/training/guards' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'father', label: t("Father's name") },
    { key: 'batch', label: t('Batch number') },
    { key: 'period', label: t('Period') },
    { key: 'path', label: t('Training path') },
    { key: 'status', label: t('Status') },
    { key: 'hr', label: t('HR status') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const isHirable = (guard: TrainingGuard): boolean =>
    ['completed', 'certified'].includes(guard.status) &&
    !guard.employee_id &&
    !guard.contractor_id;

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
    const registeredShare =
        total > 0 ? Math.round((props.stats.registered / total) * 100) : 0;

    return {
        registeredShare,
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

const pathLabel = (path?: string | null): string => {
    if (path === 'ministry') {
        return t('Public Protection Deputy');
    }
    if (path === 'company') {
        return t('Company training');
    }
    return '—';
};

const guardActions = (guard: TrainingGuard): RowActionItem[] => {
    const actions: RowActionItem[] = [
        viewAction(`/training/guards/${guard.id}`),
        editAction(`/training/guards/${guard.id}/edit`, 'training.edit'),
    ];

    if (isHirable(guard) && can('hr.create')) {
        actions.push(
            {
                label: t('Hire as employee'),
                href: `/training/guards/${guard.id}/employee`,
                method: 'post',
            },
            {
                label: t('Hire as contractor'),
                href: `/training/guards/${guard.id}/contractor`,
                method: 'post',
            },
        );
    }

    if (guard.employee_id) {
        actions.push({
            label: t('View employee'),
            href: `/hr/employees/${guard.employee_id}`,
        });
    }

    if (guard.contractor_id) {
        actions.push({
            label: t('View contractor'),
            href: `/hr/contractors/${guard.contractor_id}`,
        });
    }

    actions.push(
        deleteAction(
            {
                href: `/training/guards/${guard.id}`,
                title: t('Remove guard'),
                description: t(
                    'Are you sure you want to delete ":name"? This cannot be undone.',
                    { name: guard.name },
                ),
            },
            'training.delete',
        ),
    );

    return actions;
};
</script>

<template>
    <Head :title="t('Training')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Education and Training') }}</template>
            <template #title>{{ t('Training') }}</template>
            <template #side>
                <Link
                    v-if="can('training.create')"
                    href="/training/guards/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Register guard') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Pipeline') }}</template>
                        <template #meta
                            >{{ pipeline.registeredShare }}%
                            {{ t('Registered') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    formatNumber(stats.registered)
                                }}</strong>
                                <small>{{ t('Awaiting assignment') }}</small>
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
                                <span>{{ t(seg.label) }}</span>
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
                        :title="t('Guards')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><UserPlus /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Public Protection Deputy')"
                        :value="formatNumber(stats.ministry)"
                    >
                        <template #icon><Shield /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Company training')"
                        :value="formatNumber(stats.company)"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Certificates')"
                        :value="formatNumber(stats.certified)"
                    >
                        <template #icon><Award /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="training-guards"
            :columns="tableColumns"
            :pending="pending && guards.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search guards...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="(value) => apply({ status: value })"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="registered">{{ t('Registered') }}</option>
                        <option value="ministry">
                            {{ t('Public Protection Deputy') }}
                        </option>
                        <option value="company">{{ t('Company training') }}</option>
                        <option value="completed">{{ t('Completed') }}</option>
                        <option value="certified">{{ t('Certified') }}</option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.training_path"
                        :label="t('Training path')"
                        @change="(value) => apply({ training_path: value })"
                    >
                        <option value="">{{ t('All paths') }}</option>
                        <option value="ministry">
                            {{ t('Public Protection Deputy') }}
                        </option>
                        <option value="company">{{ t('Company training') }}</option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.batch_number"
                        :label="t('Batch number')"
                        @change="(value) => apply({ batch_number: value })"
                    >
                        <option value="">{{ t('All batches') }}</option>
                        <option
                            v-for="batch in batches ?? []"
                            :key="batch"
                            :value="batch"
                        >
                            {{ batch }}
                        </option>
                    </V2SelectFilter>
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
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="father">{{
                                t("Father's name")
                            }}</SortableTh>
                            <SortableTh column="batch">{{
                                t('Batch number')
                            }}</SortableTh>
                            <SortableTh column="period">{{ t('Period') }}</SortableTh>
                            <SortableTh column="path">{{
                                t('Training path')
                            }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th>{{ t('HR status') }}</th>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(guard, index) in sortedRows"
                            :key="guard.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="guards.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/training/guards/${guard.id}`"
                                    class="code-chip"
                                >
                                    {{ guard.name }}
                                </Link>
                            </td>
                            <td class="muted">{{ guard.father_name }}</td>
                            <td class="muted nowrap">{{ guard.batch_number }}</td>
                            <td class="muted nowrap">
                                {{ formatDate(guard.start_date) }}
                                –
                                {{ formatDate(guard.end_date) }}
                            </td>
                            <td class="muted">{{ pathLabel(guard.training_path) }}</td>
                            <td>
                                <StatusBadge :status="guard.status" />
                            </td>
                            <td>
                                <Link
                                    v-if="guard.employee"
                                    :href="`/hr/employees/${guard.employee_id}`"
                                    class="font-medium text-primary"
                                >
                                    {{ t('Employee') }}
                                </Link>
                                <Link
                                    v-else-if="guard.contractor"
                                    :href="`/hr/contractors/${guard.contractor_id}`"
                                    class="font-medium text-primary"
                                >
                                    {{ t('Contractor') }}
                                </Link>
                                <span v-else class="muted">—</span>
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="guardActions(guard)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!guards.data.length"
                            :colspan="visibleColCount"
                            :title="t('No guards registered.')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('training.create')"
                                    href="/training/guards/create"
                                    class="create-btn"
                                >
                                    <GraduationCap />
                                    {{ t('Register guard') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="guards.links?.length" #pager>
                <V2Pager :items="guards" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
