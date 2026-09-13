<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { formatNumber, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';
import {
    Briefcase,
    Building2,
    FolderKanban,
    Layers3,
    Plus,
    Trophy,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface ChartPoint {
    key: string;
    label: string;
    value: number;
}

interface OrganizationType {
    id: number;
    name: string;
    color: string | null;
}

interface Organization {
    id: number;
    name: string;
    province: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    tax_id: string | null;
    is_active: boolean;
    organization_type: OrganizationType | null;
    projects_count: number;
    procurement_opportunities_count: number;
}

const props = defineProps<{
    organizations: Paginated<Organization>;
    organizationTypes: OrganizationType[];
    stats: {
        total: number;
        active: number;
        inactive: number;
        with_projects: number;
        with_opportunities: number;
    };
    chart: {
        status: ChartPoint[];
        by_type?: ChartPoint[];
        monthly: ChartPoint[];
    };
    filters?: {
        search?: string | null;
        organization_type_id?: number | null;
        is_active?: string | null;
    };
}>();

const onlyKeys = [
    'organizations',
    'organizationTypes',
    'stats',
    'chart',
    'filters',
];

const { filters, pending, apply } = useMisFilters(
    '/organizations',
    {
        search: props.filters?.search ?? '',
        organization_type_id: props.filters?.organization_type_id ?? '',
        is_active: props.filters?.is_active ?? '',
    },
    { search: '', organization_type_id: '', is_active: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.organizations.data, {
    accessors: {
        name: (row) => row.name,
        type: (row) => row.organization_type?.name,
        location: (row) => row.province || row.address,
        contact: (row) => row.phone || row.email,
        activity: (row) => row.projects_count,
        status: (row) => (row.is_active ? 1 : 0),
    },
});
const { t, viewAction, editAction, deleteAction, gateActions, can } =
    useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Organizations', href: '/organizations' },
        ],
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Organization') },
    { key: 'type', label: t('Type') },
    { key: 'location', label: t('Location') },
    { key: 'contact', label: t('Contact') },
    { key: 'activity', label: t('Activity') },
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

const typeRows = computed(() => {
    const rows = (props.chart?.by_type ?? []).filter((row) => row.value > 0);
    const total = Math.max(
        rows.reduce((sum, row) => sum + row.value, 0),
        props.stats.total,
        1,
    );

    return rows.slice(0, 4).map((row, index) => ({
        key: row.key,
        label: row.label,
        value: row.value,
        color: statusPalette[index % statusPalette.length],
        width: Math.max(row.value > 0 ? 6 : 0, (row.value / total) * 100),
    }));
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

const organizationActions = (org: Organization): RowActionItem[] => [
    viewAction(`/organizations/${org.id}`),
    editAction(`/organizations/${org.id}/edit`, 'bidding.edit'),
    ...gateActions(
        [
            toggleIsActiveAction({
                url: `/organizations/${org.id}`,
                name: org.name,
                isActive: org.is_active,
                entityLabel: t('organization'),
                t,
            }),
        ],
        'bidding.edit',
    ),
    deleteAction(
        {
            href: `/organizations/${org.id}`,
            title: t('Delete organization'),
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                { name: org.name },
            ),
        },
        'bidding.delete',
    ),
];

function onTypeChange(value: string) {
    apply({ organization_type_id: value });
}
</script>

<template>
    <Head :title="t('Organizations')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('CRM') }}</template>
            <template #title>{{ t('Organizations') }}</template>
            <template #side>
                <Link
                    v-if="can('bidding.create')"
                    href="/organizations/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Add Organization') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Status') }}</template>
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

                        <template v-if="typeRows.length">
                            <div class="inventory-bar" aria-hidden="true">
                                <i
                                    v-for="seg in typeRows"
                                    :key="seg.key"
                                    :style="{
                                        width: `${seg.width}%`,
                                        background: seg.color,
                                    }"
                                />
                            </div>
                            <ul class="indicator-list compact">
                                <li
                                    v-for="seg in typeRows"
                                    :key="`type-${seg.key}`"
                                >
                                    <i :style="{ background: seg.color }" />
                                    <span>{{ seg.label }}</span>
                                    <b>{{ formatNumber(seg.value) }}</b>
                                </li>
                            </ul>
                        </template>
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
                        :title="t('Organizations')"
                        :value="formatNumber(stats.total)"
                    >
                        <template #icon><Building2 /></template>
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
                        :title="t('With projects')"
                        :value="formatNumber(stats.with_projects)"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('With opportunities')"
                        :value="formatNumber(stats.with_opportunities)"
                    >
                        <template #icon><Trophy /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="organizations"
            :columns="tableColumns"
            :pending="pending && organizations.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="
                                t('Search by name, email, phone, province...')
                            "
                            @submit="apply()"
                        />
                    </div>
                    <V2SelectFilter
                        v-model="filters.organization_type_id"
                        :label="t('Type')"
                        @change="onTypeChange"
                    >
                        <option value="">{{ t('All types') }}</option>
                        <option
                            v-for="type in organizationTypes"
                            :key="type.id"
                            :value="String(type.id)"
                        >
                            {{ type.name }}
                        </option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        v-model="filters.is_active"
                        :label="t('Status')"
                        @change="(value) => apply({ is_active: value })"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="1">{{ t('Active') }}</option>
                        <option value="0">{{ t('Inactive') }}</option>
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
                            <SortableTh column="name">{{ t('Organization') }}</SortableTh>
                            <SortableTh column="type">{{ t('Type') }}</SortableTh>
                            <SortableTh column="location">{{ t('Location') }}</SortableTh>
                            <SortableTh column="contact">{{ t('Contact') }}</SortableTh>
                            <SortableTh column="activity">{{ t('Activity') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(org, index) in sortedRows"
                            :key="org.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="organizations.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/organizations/${org.id}`"
                                    class="code-chip"
                                >
                                    {{ org.name }}
                                </Link>
                                <div v-if="org.tax_id" class="muted text-xs">
                                    {{ t('Tax ID') }}: {{ org.tax_id }}
                                </div>
                            </td>
                            <td>
                                {{
                                    org.organization_type?.name ?? '—'
                                }}
                            </td>
                            <td class="muted">
                                <div>{{ org.province ?? '—' }}</div>
                                <div
                                    v-if="org.address"
                                    class="max-w-xs truncate text-xs"
                                >
                                    {{ org.address }}
                                </div>
                            </td>
                            <td class="muted">
                                <div>{{ org.email ?? '—' }}</div>
                                <div class="text-xs">{{ org.phone ?? '' }}</div>
                            </td>
                            <td class="muted text-xs">
                                <span class="inline-flex items-center gap-1">
                                    <FolderKanban class="size-3" />
                                    {{
                                        t(':count projects', {
                                            count: String(org.projects_count),
                                        })
                                    }}
                                </span>
                                <span class="ms-2 inline-flex items-center gap-1">
                                    <Users class="size-3" />
                                    {{
                                        t(':count bids', {
                                            count: String(
                                                org.procurement_opportunities_count,
                                            ),
                                        })
                                    }}
                                </span>
                            </td>
                            <td>
                                {{
                                    org.is_active ? t('Active') : t('Inactive')
                                }}
                            </td>
                            <td class="end">
                                <RowActionsMenu
                                    :actions="organizationActions(org)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!organizations.data.length"
                            :colspan="visibleColCount"
                            :title="t('No organizations yet')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('bidding.create')"
                                    href="/organizations/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Create first organization') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template v-if="organizations.links?.length" #pager>
                <V2Pager :items="organizations" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
