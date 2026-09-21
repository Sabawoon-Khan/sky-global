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
import { formatAfn, formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { projectStatusActions } from '@/lib/status-actions';
import {
    Briefcase,
    Layers3,
    Plus,
    Trophy,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface Organization {
    id: number;
    name: string;
}

interface Project {
    id: number;
    code: string;
    name: string;
    status: string;
    submission_deadline: string | null;
    our_bid_amount: number | null;
    total_contract_value: number | null;
    currency: string | null;
    organization?: Organization | null;
}

interface StatusOption {
    value: string;
    label: string;
}

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

const props = defineProps<{
    projects: Paginated<Project>;
    statusOptions: StatusOption[];
    stats: {
        total: number;
        active: number;
        planning: number;
        won_or_contracted: number;
        by_status: Record<string, number>;
    };
    chart: {
        status: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: { search?: string | null; status?: string | null };
}>();

const onlyKeys = ['projects', 'statusOptions', 'stats', 'chart', 'filters'];

const { filters, pending, apply } = useMisFilters(
    '/mis/projects',
    {
        search: props.filters?.search ?? '',
        status: props.filters?.status ?? '',
    },
    { search: '', status: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.projects.data, {
    accessors: {
        code: (row) => row.code,
        name: (row) => row.name,
        client: (row) => row.organization?.name,
        bid: (row) => row.our_bid_amount,
        status: (row) => row.status,
    },
});
const { t, viewAction, editAction, deleteAction, gateActions, can } =
    useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: '/mis/projects' }],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'code', label: t('Code') },
    { key: 'name', label: t('Project') },
    { key: 'client', label: t('Client') },
    { key: 'bid', label: t('Our bid') },
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

const projectActions = (project: Project): RowActionItem[] => [
    viewAction(`/mis/projects/${project.id}`),
    editAction(`/mis/projects/${project.id}?edit=1`, 'projects.edit'),
    ...gateActions(
        projectStatusActions(project.id, project.status, t),
        'projects.edit',
    ),
    deleteAction(
        {
            href: `/mis/projects/${project.id}`,
            title: t('Delete project'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: project.name },
            ),
        },
        'projects.delete',
    ),
];

function onStatusChange(value: string) {
    apply({ status: value });
}
</script>

<template>
    <Head :title="t('Projects')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Operations') }}</template>
            <template #title>{{ t('Projects') }}</template>
            <template #side>
                <Link
                    v-if="can('projects.create')"
                    href="/mis/projects/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('New Project') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Pipeline') }}</template>
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
                        :title="t('Projects')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Active')"
                        :value="formatNumber(stats.active)"
                    >
                        <template #icon><Layers3 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Planning')"
                        :value="formatNumber(stats.planning)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Won / Contracted')"
                        :value="formatNumber(stats.won_or_contracted)"
                    >
                        <template #icon><Trophy /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="projects"
            :columns="tableColumns"
            :pending="pending && projects.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search code, name, reference...')"
                            @submit="apply()"
                        />
                    </div>
                    <V2SelectFilter
                        v-model="filters.status"
                        :label="t('Status')"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option
                            v-for="opt in statusOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
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
                            <SortableTh column="code">{{ t('Code') }}</SortableTh>
                            <SortableTh column="name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="client">{{ t('Client') }}</SortableTh>
                            <SortableTh column="bid">{{ t('Our bid') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(project, index) in sortedRows"
                            :key="project.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="projects.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/mis/projects/${project.id}`"
                                    class="code-chip"
                                >
                                    {{ project.code }}
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="`/mis/projects/${project.id}`"
                                    class="code-chip"
                                >
                                    {{ project.name }}
                                </Link>
                            </td>
                            <td class="muted">
                                {{ project.organization?.name ?? '—' }}
                            </td>
                            <td class="nums">
                                {{
                                    formatAfn(
                                        project.our_bid_amount ??
                                            project.total_contract_value,
                                    )
                                }}
                            </td>
                            <td>
                                <StatusBadge :status="project.status" />
                            </td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="projectActions(project)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!projects.data.length"
                            :colspan="visibleColCount"
                            :title="t('No projects yet')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('projects.create')"
                                    href="/mis/projects/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Create project') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="projects.links?.length" #pager>
                <V2Pager :items="projects" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
