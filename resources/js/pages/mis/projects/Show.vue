<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    ChevronDown,
    MapPin,
    Package,
    Pencil,
    Plus,
    Shield,
    Trash2,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import FileLink from '@/components/FileLink.vue';
import InputError from '@/components/InputError.vue';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { MisChartCard, MisEmptyState } from '@/components/mis';
import DonutChart from '@/components/charts/DonutChart.vue';
import RingProgress from '@/components/charts/RingProgress.vue';
import MisTabs from '@/components/MisTabs.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import FinanceCategoryField from '@/components/FinanceCategoryField.vue';
import SecurityScopeField from '@/components/SecurityScopeField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import { formatAfn, formatDate } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';

interface Organization {
    id: number;
    name: string;
    organization_type?: { name: string; color: string | null } | null;
}

interface CompetitorBid {
    id: number;
    competitor_name: string;
    bid_amount: number | null;
    currency: string | null;
    is_winner: boolean;
    is_estimated: boolean;
    notes: string | null;
}

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface CashBox {
    received: number;
    spent: number;
    remaining: number;
    currency: string;
}

interface FinanceRow {
    id: number;
    amount: number;
    currency: string;
    description: string | null;
    category?: string | null;
    transaction_date: string;
    paid_from_cash_box?: boolean;
    attachments?: FinanceAttachment[];
}

interface ProjectActivity {
    id: number;
    title: string;
    description: string | null;
    activity_type: string;
    created_at: string;
}

interface ProjectIssue {
    id: number;
    title: string;
    description: string | null;
    severity: string;
    status: string;
    category?: string | null;
    resolution_notes?: string | null;
    resolved_at?: string | null;
    opened_at: string | null;
}

interface ProjectDeployment {
    id: number;
    personnel_type: string;
    personnel_id: number;
    role: string | null;
    shift_pattern: string | null;
    start_date: string | null;
    end_date: string | null;
    monthly_rate: number | null;
    currency: string | null;
    personnel?: {
        id: number;
        first_name?: string | null;
        last_name?: string | null;
        father_name?: string | null;
        phone?: string | null;
        email?: string | null;
    } | null;
    project_site?: { id: number; name: string } | null;
}

interface ShareholderTransaction {
    id: number;
    type: string;
    amount: number;
    currency: string;
    transaction_date: string;
    notes: string | null;
}

interface ProjectShareholder {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    share_percent: number;
    invested_amount: number;
    returned_amount: number;
    currency: string;
    notes: string | null;
    transactions?: ShareholderTransaction[];
}

interface ProjectEquipmentReturn {
    id: number;
    quantity: number;
    returned_at: string;
    notes: string | null;
    received_by?: { id: number; name: string } | null;
}

interface ProjectEquipmentIssue {
    id: number;
    quantity: number;
    quantity_returned: number;
    issued_at: string;
    notes: string | null;
    equipment_catalog?: {
        id: number;
        name: string;
        sku: string | null;
        category: string | null;
        unit: string | null;
    } | null;
    issued_by?: { id: number; name: string } | null;
    returns?: ProjectEquipmentReturn[];
}

interface StockItemOption {
    id: number;
    name: string;
    sku: string | null;
    category: string | null;
    unit: string | null;
    quantity_on_hand: number;
}

interface PersonOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface OrganizationOption {
    id: number;
    name: string;
}

interface Project {
    id: number;
    code: string;
    name: string;
    reference_number: string | null;
    status: string;
    scope_summary: string | null;
    location: string | null;
    source: string | null;
    security_scope: string[] | null;
    submission_deadline: string | null;
    our_bid_amount: number | null;
    total_contract_value: number | null;
    winning_amount: number | null;
    winning_competitor_name: string | null;
    loss_reason: string | null;
    currency: string;
    contract_number: string | null;
    contract_start: string | null;
    contract_end: string | null;
    organization: Organization | null;
    competitor_bids: CompetitorBid[];
    incomes: FinanceRow[];
    expenses: FinanceRow[];
    activities: ProjectActivity[];
    issues: ProjectIssue[];
    deployments?: ProjectDeployment[];
    shareholders?: ProjectShareholder[];
    equipment_issues?: ProjectEquipmentIssue[];
    attachments: EntityAttachment[];
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    project: Project;
    finance: {
        income: number;
        expense: number;
        margin: number;
        currency: string;
        shareholder_invested?: number;
        shareholder_returned?: number;
        shareholder_outstanding?: number;
    };
    statusOptions: StatusOption[];
    organizations?: OrganizationOption[];
    employees?: PersonOption[];
    contractors?: PersonOption[];
    currencies?: string[];
    stockItems?: StockItemOption[];
    financeCategories?: Array<{
        id: number;
        name: string;
        applies_to: 'income' | 'expense' | 'both';
    }>;
    cashBox?: CashBox;
}>();

const { t, can, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: '/mis/projects' },
            { title: 'Project', href: '#' },
        ],
    },
});

const EMPLOYEE_TYPE = 'App\\Models\\Hr\\Employee';
const CONTRACTOR_TYPE = 'App\\Models\\Hr\\Contractor';

const deploymentPersonnelType = ref(EMPLOYEE_TYPE);
const shareholderAction = ref<{ id: number; type: 'contribute' | 'distribute' } | null>(null);

const tabs = computed(() => [
    { id: 'overview' as const, label: t('Overview') },
    { id: 'bid' as const, label: t('Our Bid') },
    { id: 'competitors' as const, label: t('Competitors') },
    { id: 'personnel' as const, label: t('Personnel') },
    { id: 'equipment' as const, label: t('Equipment') },
    { id: 'shareholders' as const, label: t('Shareholders') },
    { id: 'finance' as const, label: t('Finance') },
    { id: 'activity' as const, label: t('Activity') },
    { id: 'issues' as const, label: t('Reports') },
    { id: 'attachments' as const, label: t('Attachments') },
]);

type TabId =
    | 'overview'
    | 'bid'
    | 'competitors'
    | 'personnel'
    | 'equipment'
    | 'shareholders'
    | 'finance'
    | 'activity'
    | 'issues'
    | 'attachments';

const tabIds: TabId[] = [
    'overview',
    'bid',
    'competitors',
    'personnel',
    'equipment',
    'shareholders',
    'finance',
    'activity',
    'issues',
    'attachments',
];

const page = usePage();

const queryFromUrl = (url: string): URLSearchParams => {
    const query = url.includes('?') ? (url.split('?')[1] ?? '') : '';

    return new URLSearchParams(query);
};

const tabFromUrl = (url: string): TabId => {
    const fromUrl = queryFromUrl(url).get('tab');

    return tabIds.includes(fromUrl as TabId) ? (fromUrl as TabId) : 'overview';
};

const editFromUrl = (url: string = page.url): boolean =>
    queryFromUrl(url).get('edit') === '1';

const initialTab = (): TabId => tabFromUrl(page.url);

const activeTab = ref<TabId>(initialTab());

const setActiveTab = (tab: TabId): void => {
    activeTab.value = tab;
};

function syncTabFromUrl(): void {
    const fromUrl = tabFromUrl(page.url);
    if (activeTab.value !== fromUrl) {
        activeTab.value = fromUrl;
    }
}

const showProjectEditForm = ref(false);

const setEditQueryParam = (enabled: boolean): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);

    if (enabled) {
        url.searchParams.set('edit', '1');
    } else {
        url.searchParams.delete('edit');
    }

    window.history.replaceState({}, '', url.toString());
};

const openProjectEdit = (): void => {
    showProjectEditForm.value = true;
    setEditQueryParam(true);
};

const closeProjectEdit = (): void => {
    showProjectEditForm.value = false;
    setEditQueryParam(false);
};

const onProjectEditOpenChange = (open: boolean): void => {
    if (open) {
        openProjectEdit();
        return;
    }

    closeProjectEdit();
};

function syncEditFromUrl(): void {
    if (editFromUrl() && can('projects.edit')) {
        showProjectEditForm.value = true;
        return;
    }

    if (!editFromUrl()) {
        showProjectEditForm.value = false;
    }
}

onMounted(() => {
    syncTabFromUrl();
    syncEditFromUrl();
});

watch(
    () => page.url,
    () => {
        syncTabFromUrl();
        syncEditFromUrl();
    },
);

watch(activeTab, (tab) => {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
});

const today = new Date().toISOString().slice(0, 10);

const editingFinance = ref<{
    row: FinanceRow;
    type: 'income' | 'expense';
} | null>(null);
const showIncomeForm = ref(false);
const showExpenseForm = ref(false);
const showCompetitorForm = ref(false);
const showAssignForm = ref(false);
const showIssueStockForm = ref(false);
const showShareholderForm = ref(false);
const showReportForm = ref(false);
const showAttachmentForm = ref(false);

const editingIssue = ref<ProjectIssue | null>(null);

const returningIssue = ref<ProjectEquipmentIssue | null>(null);
const expandedEquipmentIds = ref<number[]>([]);

const equipmentIssues = computed(() => props.project.equipment_issues ?? []);

const { sortedRows: sortedEquipmentIssues } = provideTableSort(
    () => equipmentIssues.value,
    {
        accessors: {
            item: (row) => row.equipment_catalog?.name,
            issued: (row) => row.quantity,
            returned: (row) => row.quantity_returned,
            on_site: (row) => row.quantity - row.quantity_returned,
            status: (row) => row.quantity - row.quantity_returned,
        },
    },
);

const equipmentSummary = computed(() => {
    const issues = equipmentIssues.value;
    const totalIssued = issues.reduce((sum, issue) => sum + issue.quantity, 0);
    const totalReturned = issues.reduce(
        (sum, issue) => sum + issue.quantity_returned,
        0,
    );
    const onSite = Math.max(0, totalIssued - totalReturned);

    return { totalIssued, totalReturned, onSite };
});

const outstandingForReturn = computed(() => {
    if (!returningIssue.value) {
        return 0;
    }

    return Math.max(
        0,
        returningIssue.value.quantity - returningIssue.value.quantity_returned,
    );
});

const openReturnDialog = (issue: ProjectEquipmentIssue): void => {
    returningIssue.value = issue;
};

const closeReturnDialog = (): void => {
    returningIssue.value = null;
};

const toggleEquipmentHistory = (issueId: number): void => {
    if (expandedEquipmentIds.value.includes(issueId)) {
        expandedEquipmentIds.value = expandedEquipmentIds.value.filter(
            (id) => id !== issueId,
        );
        return;
    }

    expandedEquipmentIds.value = [...expandedEquipmentIds.value, issueId];
};

const isEquipmentHistoryOpen = (issueId: number): boolean =>
    expandedEquipmentIds.value.includes(issueId);

const isBiddingPhase = computed(() =>
    ['draft', 'submitted', 'won', 'lost'].includes(props.project.status),
);

const scopeTypeLabels: Record<string, string> = {
    static: 'Static guards',
    mobile: 'Mobile patrol',
    vip: 'VIP',
    event: 'Event',
};

const formatScopeType = (value: string): string =>
    t(scopeTypeLabels[value] ?? value);

const projectScopeTypes = computed(() => props.project.security_scope ?? []);

const statusVariant = (status: string) => {
    if (status === 'won' || status === 'active') return 'default';
    if (status === 'lost') return 'destructive';
    if (status === 'submitted') return 'secondary';
    return 'outline';
};

const changeStatus = (status: string) => {
    router.post(`/mis/projects/${props.project.id}/status`, { status }, { preserveScroll: true });
};

const markLost = () => {
    router.post(
        `/mis/projects/${props.project.id}/status`,
        {
            status: 'lost',
            loss_reason: props.project.loss_reason ?? '',
            winning_competitor_name: props.project.winning_competitor_name ?? '',
            winning_amount: props.project.winning_amount ?? '',
        },
        { preserveScroll: true },
    );
};

const financeEntryActions = (
    row: FinanceRow,
    type: 'income' | 'expense',
): RowActionItem[] => {
    const actions: RowActionItem[] = [];

    if (can('finance.edit')) {
        actions.push({
            label: t('Edit'),
            icon: Pencil,
            onClick: () => {
                editingFinance.value = { row, type };
            },
        });
    }

    if (can('finance.delete')) {
        actions.push({
            label: t('Delete'),
            icon: Trash2,
            variant: 'destructive',
            href:
                type === 'income'
                    ? `/finance/incomes/${row.id}`
                    : `/finance/expenses/${row.id}`,
            method: 'delete',
            confirm: {
                title: t(
                    type === 'income'
                        ? 'Delete income record'
                        : 'Delete expense record',
                ),
                description: t('Delete ":name"? This cannot be undone.', {
                    name: row.description ?? type,
                }),
                confirmLabel: t('Delete'),
            },
        });
    }

    return actions;
};

const issueActions = (issue: ProjectIssue): RowActionItem[] => {
    const actions: RowActionItem[] = [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => {
                editingIssue.value = issue;
            },
        },
    ];

    if (issue.status === 'open') {
        actions.push({
            label: t('Mark in progress'),
            href: `/mis/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'in_progress' },
        });
    }

    if (['open', 'in_progress'].includes(issue.status)) {
        actions.push({
            label: t('Resolve'),
            href: `/mis/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'resolved' },
        });
    }

    if (issue.status === 'resolved') {
        actions.push({
            label: t('Close'),
            href: `/mis/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'closed' },
        });
    }

    actions.push({
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/mis/projects/${props.project.id}/issues/${issue.id}`,
        method: 'delete',
        confirm: {
            title: t('Delete issue'),
            description: t('Delete ":name"? This cannot be undone.', {
                name: issue.title,
            }),
            confirmLabel: t('Delete'),
        },
    });

    return gateActions(actions, 'projects.edit');
};


const marginPct = computed(() => {
    const income = Number(props.finance.income) || 0;
    if (income <= 0) return 0;
    return Math.round((Number(props.finance.margin) / income) * 100);
});

const financeMixLabels = computed(() => [t('Income'), t('Expenses')]);
const financeMixData = computed(() => [
    Math.max(0, Number(props.finance.income) || 0),
    Math.max(0, Number(props.finance.expense) || 0),
]);

const monthlyFinanceChart = computed(() => {
    const buckets = new Map<string, { label: string; income: number; expense: number }>();

    const push = (date: string | null | undefined, amount: number, kind: 'income' | 'expense') => {
        if (!date) return;
        const d = new Date(date);
        if (Number.isNaN(d.getTime())) return;
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        const label = d.toLocaleString(undefined, { month: 'short' });
        const row = buckets.get(key) ?? { label, income: 0, expense: 0 };
        row[kind] += Number(amount) || 0;
        buckets.set(key, row);
    };

    for (const row of props.project.incomes ?? []) {
        push(row.transaction_date, row.amount, 'income');
    }
    for (const row of props.project.expenses ?? []) {
        push(row.transaction_date, row.amount, 'expense');
    }

    const rows = [...buckets.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .slice(-6)
        .map(([, row]) => row);

    if (!rows.length) {
        return {
            labels: ['—'],
            datasets: [
                { label: t('Income'), data: [0], color: 'var(--chart-2)' },
                { label: t('Expenses'), data: [0], color: 'var(--chart-3)' },
            ],
        };
    }

    return {
        labels: rows.map((row) => row.label),
        datasets: [
            {
                label: t('Income'),
                data: rows.map((row) => row.income),
                color: 'var(--chart-2)',
            },
            {
                label: t('Expenses'),
                data: rows.map((row) => row.expense),
                color: 'var(--chart-3)',
            },
        ],
    };
});

const competitorChart = computed(() => {
    const ours = Number(props.project.our_bid_amount) || 0;
    const rows = [
        { label: t('Our bid'), value: ours },
        ...props.project.competitor_bids.map((c) => ({
            label: c.competitor_name,
            value: Number(c.bid_amount) || 0,
        })),
    ].filter((row) => row.value > 0);

    return {
        labels: rows.length ? rows.map((r) => r.label) : [t('Our bid')],
        datasets: [
            {
                label: t('Bid amount'),
                data: rows.length ? rows.map((r) => r.value) : [0],
                color: 'var(--chart-1)',
            },
        ],
    };
});

const overviewCounts = computed(() => ({
    personnel: props.project.deployments?.length ?? 0,
    equipment: props.project.equipment_issues?.length ?? 0,
    issues: props.project.issues?.length ?? 0,
    attachments: props.project.attachments?.length ?? 0,
    activities: props.project.activities?.length ?? 0,
    competitors: props.project.competitor_bids?.length ?? 0,
}));

const isEmployeeDeployment = (deployment: ProjectDeployment): boolean =>
    deployment.personnel_type.includes('Employee');

const deploymentPersonName = (deployment: ProjectDeployment): string => {
    const fromRelation = [deployment.personnel?.first_name, deployment.personnel?.last_name]
        .filter(Boolean)
        .join(' ')
        .trim();

    if (fromRelation) {
        return fromRelation;
    }

    const pool = isEmployeeDeployment(deployment)
        ? (props.employees ?? [])
        : (props.contractors ?? []);
    const match = pool.find((person) => person.id === deployment.personnel_id);

    if (match) {
        return `${match.first_name} ${match.last_name}`.trim();
    }

    return isEmployeeDeployment(deployment)
        ? `${t('Employee')} #${deployment.personnel_id}`
        : `${t('Contractor')} #${deployment.personnel_id}`;
};

const deploymentPersonHref = (deployment: ProjectDeployment): string =>
    isEmployeeDeployment(deployment)
        ? `/hr/employees/${deployment.personnel_id}`
        : `/hr/contractors/${deployment.personnel_id}`;

const deploymentDateRange = (deployment: ProjectDeployment): string => {
    const start = formatDate(deployment.start_date);
    const end = deployment.end_date ? formatDate(deployment.end_date) : t('Ongoing');

    return `${start} — ${end}`;
};

const recentActivity = computed(() => (props.project.activities ?? []).slice(0, 5));

const closeFinanceEdit = (): void => {
    editingFinance.value = null;
};

const closeIssueEdit = (): void => {
    editingIssue.value = null;
};
</script>

<template>
    <Head :title="project.name" />

    <V2ListPage>
        <V2DetailHero
            image="/images/gs-hero-operations.png"
            :compact="activeTab !== 'overview'"
        >
            <template #eyebrow>{{ t('Projects') }}</template>
            <template #title>{{ project.name }}</template>
            <template #description>
                {{ project.code }}
                <span v-if="project.organization?.name">
                    · {{ project.organization.name }}
                </span>
            </template>
            <template #actions>
                <div class="mb-2 flex flex-wrap gap-2">
                    <Badge :variant="statusVariant(project.status)">{{
                        project.status
                    }}</Badge>
                    <Badge v-if="project.organization" variant="secondary">
                        {{ project.organization.name }}
                    </Badge>
                    <Badge
                        v-for="scope in projectScopeTypes"
                        :key="scope"
                        variant="outline"
                    >
                        {{ formatScopeType(scope) }}
                    </Badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/mis/projects">{{ t('Back to list') }}</Link>
                    </Button>
                    <Button
                        v-if="can('projects.edit')"
                        size="sm"
                        variant="outline"
                        @click="openProjectEdit"
                    >
                        <Pencil class="me-1 size-4" />
                        {{ t('Edit') }}
                    </Button>
                    <template v-if="can('projects.edit') && statusOptions.length">
                        <Button
                            v-for="opt in statusOptions"
                            :key="opt.value"
                            size="sm"
                            :variant="
                                opt.value === 'lost' ? 'destructive' : 'default'
                            "
                            @click="
                                opt.value === 'lost'
                                    ? markLost()
                                    : changeStatus(opt.value)
                            "
                        >
                            Mark {{ opt.label }}
                        </Button>
                    </template>
                </div>
            </template>
            <template v-if="activeTab === 'overview'" #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Our bid')"
                        :value="formatAfn(project.our_bid_amount)"
                    />
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Deadline')"
                        :value="formatDate(project.submission_deadline)"
                    />
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Competitors')"
                        :value="project.competitor_bids.length"
                    />
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Margin')"
                        :value="formatAfn(finance.margin)"
                    />
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <MisTabs
            :model-value="activeTab"
            :tabs="tabs"
            nowrap
            @update:model-value="(id) => setActiveTab(id as TabId)"
        />

        <!-- Overview -->
        <div v-if="activeTab === 'overview'" class="project-overview space-y-4">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="overview-kpi">
                    <div class="overview-kpi-top">
                        <span>{{ t('Income') }}</span>
                        <span class="overview-kpi-icon income"><ArrowUpRight /></span>
                    </div>
                    <strong>{{ formatAfn(finance.income) }}</strong>
                    <small>{{ t('Project receipts') }}</small>
                </article>
                <article class="overview-kpi">
                    <div class="overview-kpi-top">
                        <span>{{ t('Expenses') }}</span>
                        <span class="overview-kpi-icon expense"><ArrowDownRight /></span>
                    </div>
                    <strong>{{ formatAfn(finance.expense) }}</strong>
                    <small>{{ t('Project spend') }}</small>
                </article>
                <article class="overview-kpi">
                    <div class="overview-kpi-top">
                        <span>{{ t('Margin') }}</span>
                        <RingProgress
                            :value="Math.abs(marginPct)"
                            :max="100"
                            :size="40"
                            :stroke-width="4"
                            :color="finance.margin >= 0 ? '#1f4e5f' : '#8f2d3a'"
                            track-color="rgba(12, 26, 46, 0.08)"
                        />
                    </div>
                    <strong :class="finance.margin >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'">
                        {{ formatAfn(finance.margin) }}
                    </strong>
                    <small>{{ marginPct }}% {{ t('of income') }}</small>
                </article>
                <article class="overview-kpi">
                    <div class="overview-kpi-top">
                        <span>{{ t('Workforce') }}</span>
                        <span class="overview-kpi-icon people"><Users /></span>
                    </div>
                    <strong>{{ overviewCounts.personnel }}</strong>
                    <small>
                        {{ overviewCounts.equipment }} {{ t('equipment') }}
                        · {{ overviewCounts.issues }} {{ t('Reports') }}
                    </small>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-12">
                <MisChartCard
                    class="xl:col-span-7"
                    :title="t('Income vs expenses')"
                    type="bar"
                    :labels="monthlyFinanceChart.labels"
                    :datasets="monthlyFinanceChart.datasets"
                    :show-values="false"
                />

                <V2Panel
                    class="xl:col-span-5"
                    :title="t('Finance mix')"
                >
                    <div class="relative mx-auto h-[220px] w-full max-w-[260px]">
                        <DonutChart
                            :labels="financeMixLabels"
                            :data="financeMixData"
                            :colors="['#1f4e5f', '#b8956c']"
                            :height="220"
                            :center-label="t('Net')"
                            :center-value="formatAfn(finance.margin)"
                        />
                    </div>
                </V2Panel>
            </section>

            <section class="grid gap-4 xl:grid-cols-12">
                <V2Panel
                    class="xl:col-span-5"
                    :title="t('Project info')"
                >
                    <ul class="overview-meta">
                        <li>
                            <MapPin class="size-4" />
                            <div>
                                <span>{{ t('Location') }}</span>
                                <strong>{{ project.location ?? '—' }}</strong>
                            </div>
                        </li>
                        <li>
                            <Shield class="size-4" />
                            <div>
                                <span>{{ t('Scope') }}</span>
                                <strong>
                                    <template v-if="projectScopeTypes.length">
                                        {{ projectScopeTypes.map(formatScopeType).join(', ') }}
                                    </template>
                                    <template v-else>—</template>
                                </strong>
                            </div>
                        </li>
                        <li>
                            <Wallet class="size-4" />
                            <div>
                                <span>{{ t('Our bid') }}</span>
                                <strong>{{ formatAfn(project.our_bid_amount) }}</strong>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-if="project.scope_summary"
                        class="mt-4 rounded-xl bg-muted/40 px-3 py-3 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ project.scope_summary }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Badge variant="outline">
                            {{ overviewCounts.competitors }} {{ t('Competitors') }}
                        </Badge>
                        <Badge variant="outline">
                            {{ overviewCounts.attachments }} {{ t('Attachments') }}
                        </Badge>
                        <Badge variant="outline">
                            {{ overviewCounts.activities }} {{ t('Activity') }}
                        </Badge>
                    </div>
                </V2Panel>

                <MisChartCard
                    class="xl:col-span-7"
                    :title="t('Bid comparison')"
                    type="bar"
                    orientation="horizontal"
                    :labels="competitorChart.labels"
                    :datasets="competitorChart.datasets"
                    :show-values="true"
                    :page-size="6"
                />
            </section>

            <section class="grid gap-4 xl:grid-cols-12">
                <V2Panel
                    class="xl:col-span-5"
                    :title="t('Recent activity')"
                >
                    <div v-if="!recentActivity.length" class="py-8 text-center text-sm text-muted-foreground">
                        {{ t('No activity yet.') }}
                    </div>
                    <ol v-else class="overview-timeline">
                        <li v-for="item in recentActivity" :key="item.id">
                            <i />
                            <div>
                                <strong>{{ item.title }}</strong>
                                <p v-if="item.description">{{ item.description }}</p>
                                <small>{{ formatDate(item.created_at) }} · {{ item.activity_type }}</small>
                            </div>
                        </li>
                    </ol>
                </V2Panel>

                <V2Panel
                    v-if="!isBiddingPhase || project.status === 'won'"
                    class="xl:col-span-7"
                    :title="t('Contract (when won)')"
                >
                    <Form
                        v-if="can('projects.edit')"
                        v-bind="ProjectController.update.form(project.id)"
                        class="grid gap-3 sm:grid-cols-2"
                        v-slot="{ errors, processing }"
                        :options="{ preserveScroll: true }"
                    >
                        <div class="grid gap-1.5">
                            <Label for="contract_number">{{ t('Contract #') }}</Label>
                            <Input
                                id="contract_number"
                                name="contract_number"
                                :default-value="project.contract_number ?? ''"
                            />
                            <InputError :message="errors.contract_number" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="total_contract_value">{{ t('Contract value (AFN)') }}</Label>
                            <Input
                                id="total_contract_value"
                                name="total_contract_value"
                                type="number"
                                min="0"
                                step="0.01"
                                :default-value="project.total_contract_value ?? project.our_bid_amount ?? ''"
                            />
                            <InputError :message="errors.total_contract_value" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="contract_start">{{ t('Contract start') }}</Label>
                            <Input
                                id="contract_start"
                                name="contract_start"
                                type="date"
                                :default-value="project.contract_start?.slice(0, 10) ?? ''"
                            />
                            <InputError :message="errors.contract_start" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="contract_end">{{ t('Contract end') }}</Label>
                            <Input
                                id="contract_end"
                                name="contract_end"
                                type="date"
                                :default-value="project.contract_end?.slice(0, 10) ?? ''"
                            />
                            <InputError :message="errors.contract_end" />
                        </div>
                        <div class="sm:col-span-2">
                            <Button type="submit" size="sm" :disabled="processing">
                                {{ t('Save contract') }}
                            </Button>
                        </div>
                    </Form>
                    <div v-else class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ t('Contract value') }}</span>
                            <span>{{ formatAfn(project.total_contract_value) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ t('Period') }}</span>
                            <span>{{ formatDate(project.contract_start) }} – {{ formatDate(project.contract_end) }}</span>
                        </div>
                    </div>
                </V2Panel>

                <V2Panel
                    v-if="project.status === 'lost'"
                    class="xl:col-span-7 border-destructive/20"
                    :title="t('Loss details')"
                >
                    <p v-if="project.loss_reason" class="text-sm">{{ project.loss_reason }}</p>
                    <p v-if="project.winning_competitor_name" class="mt-2 text-sm text-muted-foreground">
                        {{ t('Winner:') }} {{ project.winning_competitor_name }}
                        ({{ formatAfn(project.winning_amount) }})
                    </p>
                </V2Panel>
            </section>
        </div>

        <!-- Our Bid -->
        <Card v-else-if="activeTab === 'bid'">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">{{ t('Our bid details') }}</CardTitle>
                </CardHeader>
            <CardContent>
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    class="grid gap-3 sm:grid-cols-2"
                    v-slot="{ errors, processing }"
                    :options="{ preserveScroll: true }"
                >
                    <div class="grid gap-1.5">
                        <Label for="our_bid_amount">{{ t('Our bid amount (AFN)') }}</Label>
                        <Input
                            id="our_bid_amount"
                            name="our_bid_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :default-value="project.our_bid_amount ?? ''"
                        />
                        <InputError :message="errors.our_bid_amount" />
                    </div>
                    <input type="hidden" name="currency" value="AFN" />
                    <div class="grid gap-1.5">
                        <Label for="submission_deadline">{{ t('Deadline') }}</Label>
                        <Input
                            id="submission_deadline"
                            name="submission_deadline"
                            type="date"
                            :default-value="project.submission_deadline?.slice(0, 10) ?? ''"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="reference_number">{{ t('Reference #') }}</Label>
                        <Input
                            id="reference_number"
                            name="reference_number"
                            :default-value="project.reference_number ?? ''"
                        />
                    </div>
                    <SecurityScopeField
                        :selected="project.security_scope"
                        :error="errors.security_scope"
                        include-marker
                    />
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="scope_summary">{{ t('Scope summary') }}</Label>
                        <textarea
                            id="scope_summary"
                            name="scope_summary"
                            rows="3"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            :default-value="project.scope_summary ?? ''"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" size="sm" :disabled="processing">{{ t('Save bid details') }}</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <!-- Competitors -->
        <div v-else-if="activeTab === 'competitors'" class="space-y-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Other bidders (optional)') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="project.competitor_bids.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        No competitor prices recorded yet.
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="comp in project.competitor_bids"
                            :key="comp.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div>
                                <p class="font-medium">{{ comp.competitor_name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatAfn(comp.bid_amount) }}
                                    <span v-if="comp.is_estimated"> {{ t('(estimated)') }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge v-if="comp.is_winner" variant="secondary">{{ t('Winning') }}</Badge>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="router.delete(`/mis/projects/${project.id}/competitors/${comp.id}`, { preserveScroll: true })"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Can permission="bidding.view_competitors">
            <div class="flex justify-end">
                <Button
                    variant="outline"
                    size="sm"
                    @click="showCompetitorForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Add competitor') }}
                </Button>
            </div>
            </Can>
        </div>

        <!-- Personnel -->
        <div v-else-if="activeTab === 'personnel'" class="grid gap-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Assigned Personnel') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!project.deployments?.length" class="py-6 text-center text-sm text-muted-foreground">
                        {{ t('No personnel assigned yet.') }}
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="deployment in project.deployments"
                            :key="deployment.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Link
                                        :href="deploymentPersonHref(deployment)"
                                        class="font-medium hover:underline"
                                    >
                                        {{ deploymentPersonName(deployment) }}
                                    </Link>
                                    <Badge variant="secondary">
                                        {{ isEmployeeDeployment(deployment) ? t('Employee') : t('Contractor') }}
                                    </Badge>
                                </div>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    <span v-if="deployment.role">{{ deployment.role }}</span>
                                    <span v-if="deployment.role && deployment.project_site?.name"> · </span>
                                    <span v-if="deployment.project_site?.name">{{ deployment.project_site.name }}</span>
                                </p>
                                <p
                                    v-if="deployment.personnel?.father_name"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ t('Father') }}: {{ deployment.personnel.father_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ deploymentDateRange(deployment) }}
                                    <span v-if="deployment.personnel?.phone">
                                        · {{ deployment.personnel.phone }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span v-if="deployment.monthly_rate" class="text-sm font-medium">
                                    {{ formatAfn(deployment.monthly_rate) }}/{{ t('mo') }}
                                </span>
                                <Can permission="projects.delete">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="
                                        router.delete(
                                            `/mis/projects/${project.id}/deployments/${deployment.id}`,
                                            { preserveScroll: true },
                                        )
                                    "
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                                </Can>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Can permission="projects.create">
            <div>
                <Button
                    variant="outline"
                    size="sm"
                    @click="showAssignForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Assign person') }}
                </Button>
            </div>
            </Can>
        </div>

        <!-- Equipment from stock -->
        <div v-else-if="activeTab === 'equipment'" class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Badge variant="secondary" class="px-3 py-1 text-sm">
                    {{ t('On site') }}: {{ equipmentSummary.onSite }}
                </Badge>
                <Badge variant="outline" class="px-3 py-1 text-sm">
                    {{ t('Issued') }}: {{ equipmentSummary.totalIssued }}
                </Badge>
                <Badge variant="outline" class="px-3 py-1 text-sm">
                    {{ t('Returned') }}: {{ equipmentSummary.totalReturned }}
                </Badge>
            </div>

            <div class="space-y-4">
                <V2Panel
                    :title="t('Equipment on this project')"
                >
                    <MisEmptyState
                        v-if="!equipmentIssues.length"
                        :icon="Package"
                        :title="t('No equipment on site yet')"
                    >
                        <template v-if="can('inventory.create')" #actions>
                            <Button
                                type="button"
                                size="sm"
                                @click="showIssueStockForm = true"
                            >
                                {{ t('Issue from stock') }}
                            </Button>
                        </template>
                    </MisEmptyState>

                    <div v-else class="overflow-x-auto rounded-xl border border-border/80">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <SortableTh column="item" class="px-3 py-2.5 text-start font-medium">{{ t('Item') }}</SortableTh>
                                    <SortableTh column="issued" align="end" class="px-3 py-2.5 text-end font-medium">{{ t('Issued') }}</SortableTh>
                                    <SortableTh column="returned" align="end" class="px-3 py-2.5 text-end font-medium">{{ t('Returned') }}</SortableTh>
                                    <SortableTh column="on_site" align="end" class="px-3 py-2.5 text-end font-medium">{{ t('On site') }}</SortableTh>
                                    <SortableTh column="status" align="center" class="px-3 py-2.5 text-center font-medium">{{ t('Status') }}</SortableTh>
                                    <th class="px-3 py-2.5 text-end font-medium">{{ t('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template
                                    v-for="issue in sortedEquipmentIssues"
                                    :key="issue.id"
                                >
                                    <tr class="hover:bg-muted/30">
                                        <td class="px-3 py-3">
                                            <p class="font-medium">
                                                {{ issue.equipment_catalog?.name ?? t('Item') }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">
                                                {{ formatDate(issue.issued_at) }}
                                                <span v-if="issue.equipment_catalog?.category">
                                                    · {{ issue.equipment_catalog.category }}
                                                </span>
                                                <span v-if="issue.issued_by?.name">
                                                    · {{ t('by') }} {{ issue.issued_by.name }}
                                                </span>
                                            </p>
                                            <p
                                                v-if="issue.notes"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ issue.notes }}
                                            </p>
                                        </td>
                                        <td class="px-3 py-3 text-end tabular-nums">
                                            {{ issue.quantity }}
                                        </td>
                                        <td class="px-3 py-3 text-end tabular-nums">
                                            {{ issue.quantity_returned }}
                                        </td>
                                        <td class="px-3 py-3 text-end font-semibold tabular-nums">
                                            {{ issue.quantity - issue.quantity_returned }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <Badge
                                                :variant="
                                                    issue.quantity - issue.quantity_returned > 0
                                                        ? 'default'
                                                        : 'secondary'
                                                "
                                            >
                                                {{
                                                    issue.quantity - issue.quantity_returned > 0
                                                        ? t('On site')
                                                        : t('Fully returned')
                                                }}
                                            </Badge>
                                        </td>
                                        <td class="px-3 py-3 text-end">
                                            <div class="inline-flex items-center gap-1">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    :aria-expanded="isEquipmentHistoryOpen(issue.id)"
                                                    @click="toggleEquipmentHistory(issue.id)"
                                                >
                                                    <ChevronDown
                                                        class="size-4 transition"
                                                        :class="{
                                                            'rotate-180': isEquipmentHistoryOpen(issue.id),
                                                        }"
                                                    />
                                                    <span class="sr-only">{{ t('History') }}</span>
                                                </Button>
                                                <Can permission="inventory.edit">
                                                    <Button
                                                        v-if="issue.quantity - issue.quantity_returned > 0"
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="openReturnDialog(issue)"
                                                    >
                                                        {{ t('Return') }}
                                                    </Button>
                                                </Can>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="isEquipmentHistoryOpen(issue.id)"
                                        class="bg-muted/20"
                                    >
                                        <td colspan="6" class="px-3 py-3">
                                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                {{ t('Return history') }}
                                            </p>
                                            <div
                                                v-if="!issue.returns?.length"
                                                class="text-sm text-muted-foreground"
                                            >
                                                {{ t('No returns recorded yet.') }}
                                            </div>
                                            <ul v-else class="space-y-2">
                                                <li
                                                    v-for="ret in issue.returns"
                                                    :key="ret.id"
                                                    class="rounded-lg border border-border/70 bg-background px-3 py-2 text-sm"
                                                >
                                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                                        <span class="font-medium tabular-nums">
                                                            {{ ret.quantity }}
                                                            {{ issue.equipment_catalog?.unit ?? t('pcs') }}
                                                        </span>
                                                        <span class="text-xs text-muted-foreground">
                                                            {{ formatDate(ret.returned_at) }}
                                                        </span>
                                                    </div>
                                                    <p
                                                        v-if="ret.received_by?.name"
                                                        class="mt-0.5 text-xs text-muted-foreground"
                                                    >
                                                        {{ t('Received by') }}:
                                                        {{ ret.received_by.name }}
                                                    </p>
                                                    <p
                                                        v-if="ret.notes"
                                                        class="mt-1 text-xs text-muted-foreground"
                                                    >
                                                        {{ ret.notes }}
                                                    </p>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </V2Panel>

                <Can permission="inventory.create">
                    <div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showIssueStockForm = true"
                        >
                            <Plus class="size-4" />
                            {{ t('Issue from stock') }}
                        </Button>
                    </div>
                </Can>
            </div>
        </div>

        <!-- Shareholders -->
        <div v-else-if="activeTab === 'shareholders'" class="space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('Capital received') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tabular-nums">
                            {{ formatAfn(finance.shareholder_invested ?? 0) }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('Project spent') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tabular-nums text-destructive">
                            {{ formatAfn(finance.expense) }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('Still owed to shareholders') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tabular-nums">
                            {{ formatAfn(finance.shareholder_outstanding ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('Returned') }}: {{ formatAfn(finance.shareholder_returned ?? 0) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-3 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('Shareholders') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-if="!project.shareholders?.length"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No shareholders on this project. Add partners who invest capital.') }}
                        </div>
                        <div
                            v-for="shareholder in project.shareholders"
                            :key="shareholder.id"
                            class="rounded-md border p-3"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium">{{ shareholder.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ shareholder.share_percent }}% ·
                                        {{ shareholder.phone || shareholder.email || t('No contact') }}
                                    </p>
                                </div>
                                <div class="text-end text-sm">
                                    <p>
                                        {{ t('Invested') }}:
                                        <span class="font-semibold">{{ formatAfn(shareholder.invested_amount) }}</span>
                                    </p>
                                    <p>
                                        {{ t('Returned') }}:
                                        <span class="font-semibold">{{ formatAfn(shareholder.returned_amount) }}</span>
                                    </p>
                                    <p>
                                        {{ t('Outstanding') }}:
                                        <span class="font-semibold">
                                            {{
                                                formatAfn(
                                                    Math.max(
                                                        0,
                                                        Number(shareholder.invested_amount) -
                                                            Number(shareholder.returned_amount),
                                                    ),
                                                )
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Can permission="projects.edit">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="
                                            shareholderAction =
                                                shareholderAction?.id === shareholder.id &&
                                                shareholderAction.type === 'contribute'
                                                    ? null
                                                    : { id: shareholder.id, type: 'contribute' }
                                        "
                                    >
                                        {{ t('Add capital') }}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="
                                            shareholderAction =
                                                shareholderAction?.id === shareholder.id &&
                                                shareholderAction.type === 'distribute'
                                                    ? null
                                                    : { id: shareholder.id, type: 'distribute' }
                                        "
                                    >
                                        {{ t('Return share') }}
                                    </Button>
                                </Can>
                                <Can permission="projects.delete">
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="
                                            router.delete(
                                                `/mis/projects/${project.id}/shareholders/${shareholder.id}`,
                                                { preserveScroll: true },
                                            )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </Can>
                            </div>
                            <div
                                v-if="shareholder.transactions?.length"
                                class="mt-3 border-t pt-2 text-xs text-muted-foreground"
                            >
                                <p
                                    v-for="tx in shareholder.transactions"
                                    :key="tx.id"
                                    class="flex justify-between gap-2 py-0.5"
                                >
                                    <span>
                                        {{ tx.type === 'contribution' ? t('In') : t('Out') }}
                                        · {{ formatDate(tx.transaction_date) }}
                                        <span v-if="tx.notes"> — {{ tx.notes }}</span>
                                    </span>
                                    <span class="font-medium tabular-nums">{{ formatAfn(tx.amount) }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Can permission="projects.create">
                    <div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showShareholderForm = true"
                        >
                            <Plus class="size-4" />
                            {{ t('Add shareholder') }}
                        </Button>
                    </div>
                </Can>
            </div>
        </div>

        <!-- Finance -->
        <div v-else-if="activeTab === 'finance'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-3">
                <CardContent class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">{{ t('Income') }}</p>
                        <p class="text-xl font-bold">{{ formatAfn(finance.income) }}</p>
                    </div>
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">{{ t('Expenses') }}</p>
                        <p class="text-xl font-bold">{{ formatAfn(finance.expense) }}</p>
                    </div>
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">{{ t('Margin') }}</p>
                        <p class="text-xl font-bold">{{ formatAfn(finance.margin) }}</p>
                    </div>
                </CardContent>
            </Card>

            <Can permission="finance.create">
            <div class="lg:col-span-3 flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="showIncomeForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Record payment') }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    @click="showExpenseForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Add expense') }}
                </Button>
            </div>
            </Can>

            <Card class="lg:col-span-3">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Recent entries') }}</CardTitle>
                </CardHeader>
                <CardContent class="max-h-64 space-y-2 overflow-y-auto text-sm">
                    <p v-if="!project.incomes.length && !project.expenses.length" class="text-muted-foreground">
                        No entries yet.
                    </p>
                    <div
                        v-for="row in project.incomes"
                        :key="`i-${row.id}`"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="min-w-0">
                            <span class="text-green-600 dark:text-green-400">
                                + {{ row.description ?? t('Payment received') }}
                            </span>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(row.transaction_date) }}
                                <span v-if="row.category"> · {{ row.category }}</span>
                                <FileLink
                                    v-for="file in row.attachments ?? []"
                                    :key="file.id"
                                    :href="file.download_url"
                                    :label="file.original_filename"
                                    show-icon
                                    compact
                                    class="ms-2"
                                />
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span>{{ formatAfn(row.amount) }}</span>
                            <RowActionsMenu :actions="financeEntryActions(row, 'income')" />
                        </div>
                    </div>
                    <div
                        v-for="row in project.expenses"
                        :key="`e-${row.id}`"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="min-w-0">
                            <span class="text-red-600 dark:text-red-400">
                                − {{ row.description ?? t('Expense') }}
                            </span>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(row.transaction_date) }}
                                <span v-if="row.category"> · {{ row.category }}</span>
                                <span v-if="row.paid_from_cash_box">
                                    · {{ t('Cash box') }}
                                </span>
                                <FileLink
                                    v-for="file in row.attachments ?? []"
                                    :key="file.id"
                                    :href="file.download_url"
                                    :label="file.original_filename"
                                    show-icon
                                    compact
                                    class="ms-2"
                                />
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span>{{ formatAfn(row.amount) }}</span>
                            <RowActionsMenu :actions="financeEntryActions(row, 'expense')" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Activity -->
        <Card v-else-if="activeTab === 'activity'">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">{{ t('Activity timeline') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!project.activities.length" class="py-6 text-center text-sm text-muted-foreground">
                    No activity yet.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="act in project.activities"
                        :key="act.id"
                        class="flex gap-3 border-l-2 border-muted pl-3"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ act.title }}</p>
                            <p v-if="act.description" class="text-xs text-muted-foreground">{{ act.description }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(act.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Reports / Issues -->
        <div v-else-if="activeTab === 'issues'" class="space-y-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Incident Reports') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!project.issues.length" class="py-6 text-center text-sm text-muted-foreground">
                        {{ t('No reports yet.') }}
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="issue in project.issues"
                            :key="issue.id"
                            class="flex items-start justify-between gap-3 py-4"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">{{ issue.title }}</p>
                                    <Badge v-if="issue.category" variant="secondary">{{ issue.category }}</Badge>
                                </div>
                                <p v-if="issue.description" class="mt-2 text-sm">
                                    <span class="font-medium text-muted-foreground">{{ t('What happened:') }}</span>
                                    {{ issue.description }}
                                </p>
                                <p
                                    v-if="issue.resolution_notes"
                                    class="mt-2 rounded-md bg-green-50 p-2 text-sm dark:bg-green-950/30"
                                >
                                    <span class="font-medium text-green-700 dark:text-green-400">{{ t('How we fixed it:') }}</span>
                                    {{ issue.resolution_notes }}
                                </p>
                                <p
                                    v-else-if="['resolved', 'closed'].includes(issue.status)"
                                    class="mt-2 text-sm text-muted-foreground italic"
                                >
                                    {{ t('Resolved — add resolution notes via Edit') }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge
                                        :variant="
                                            issue.status === 'resolved' || issue.status === 'closed'
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ issue.status }}
                                    </Badge>
                                    <Badge
                                        :variant="
                                            issue.severity === 'high' ||
                                            issue.severity === 'critical'
                                                ? 'destructive'
                                                : 'secondary'
                                        "
                                    >
                                        {{ issue.severity }}
                                    </Badge>
                                    <span v-if="issue.resolved_at" class="text-xs text-muted-foreground">
                                        {{ t('Resolved') }} {{ formatDate(issue.resolved_at) }}
                                    </span>
                                </div>
                            </div>
                            <RowActionsMenu :actions="issueActions(issue)" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Can permission="projects.edit">
            <div>
                <Button
                    variant="outline"
                    size="sm"
                    @click="showReportForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('New report') }}
                </Button>
            </div>
            </Can>
        </div>

        <!-- Attachments -->
        <div v-else-if="activeTab === 'attachments'" class="space-y-3">
            <EntityAttachments
                :attachments="project.attachments"
            />
            <Can permission="projects.edit">
            <div>
                <Button
                    variant="outline"
                    size="sm"
                    @click="showAttachmentForm = true"
                >
                    <Plus class="size-4" />
                    {{ t('Add attachment') }}
                </Button>
            </div>
            </Can>
        </div>

        <Dialog
            :open="showCompetitorForm"
            @update:open="showCompetitorForm = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/competitors`"
                    method="post"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ processing }"
                    @success="showCompetitorForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Add competitor') }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <Input name="competitor_name" :placeholder="t('Company name')" required />
                        <Input name="bid_amount" type="number" min="0" step="0.01" :placeholder="t('Amount (AFN)')" />
                        <input type="hidden" name="currency" value="AFN" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_estimated" value="1" />
                            Estimated price
                        </label>
                        <OptionalAttachmentField />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showCompetitorForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">{{ t('Add') }}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showAssignForm" @update:open="showAssignForm = $event">
            <DialogContent class="sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/deployments`"
                    method="post"
                    :options="{ preserveScroll: true }"
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showAssignForm = false;
                            setActiveTab('personnel');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Assign person') }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <input type="hidden" name="personnel_type" :value="deploymentPersonnelType" />
                        <select
                            v-model="deploymentPersonnelType"
                            class="h-9 rounded-md border border-input px-3 text-sm"
                        >
                            <option :value="EMPLOYEE_TYPE">{{ t('Employee') }}</option>
                            <option :value="CONTRACTOR_TYPE">{{ t('Contractor') }}</option>
                        </select>
                        <select
                            name="personnel_id"
                            required
                            class="h-9 rounded-md border border-input px-3 text-sm"
                        >
                            <option value="" disabled selected>{{ t('Select person') }}</option>
                            <option
                                v-for="person in deploymentPersonnelType === EMPLOYEE_TYPE ? (employees ?? []) : (contractors ?? [])"
                                :key="person.id"
                                :value="person.id"
                            >
                                {{ person.first_name }} {{ person.last_name }}
                            </option>
                        </select>
                        <InputError :message="errors.personnel_id" />
                        <Input name="role" :placeholder="t('Role on site')" />
                        <div class="grid gap-1">
                            <Label for="deployment_start_date">{{ t('Start date') }}</Label>
                            <Input id="deployment_start_date" name="start_date" type="date" />
                        </div>
                        <div class="grid gap-1">
                            <Label for="deployment_end_date">{{ t('End date') }}</Label>
                            <Input id="deployment_end_date" name="end_date" type="date" />
                        </div>
                        <Input name="monthly_rate" type="number" min="0" step="0.01" :placeholder="t('Monthly rate (AFN)')" />
                        <input type="hidden" name="currency" value="AFN" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showAssignForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">{{ t('Assign') }}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showIssueStockForm"
            @update:open="showIssueStockForm = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/equipment-issues`"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showIssueStockForm = false;
                            setActiveTab('equipment');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Issue from stock') }}</DialogTitle>
                        <DialogDescription>
                            {{ t('Stock is reduced from the depot when you issue items.') }}
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="issue-equipment-catalog">{{ t('Item') }}</Label>
                            <select
                                id="issue-equipment-catalog"
                                name="equipment_catalog_id"
                                required
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled selected>{{ t('Select item') }}</option>
                                <option
                                    v-for="item in stockItems ?? []"
                                    :key="item.id"
                                    :value="item.id"
                                    :disabled="item.quantity_on_hand < 1"
                                >
                                    {{ item.name }}
                                    <template v-if="item.category"> ({{ item.category }})</template>
                                    — {{ item.quantity_on_hand }} {{ item.unit ?? 'pcs' }}
                                </option>
                            </select>
                            <InputError :message="errors.equipment_catalog_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="issue-quantity">{{ t('Quantity') }}</Label>
                            <Input
                                id="issue-quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                required
                                :placeholder="t('Quantity')"
                            />
                            <InputError :message="errors.quantity" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="issue-issued-at">{{ t('Issued at') }}</Label>
                            <Input
                                id="issue-issued-at"
                                name="issued_at"
                                type="date"
                                :default-value="today"
                            />
                            <InputError :message="errors.issued_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="issue-notes">{{ t('Notes') }}</Label>
                            <Textarea
                                id="issue-notes"
                                name="notes"
                                rows="2"
                                :placeholder="t('Notes')"
                            />
                            <InputError :message="errors.notes" />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showIssueStockForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Issue to project') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showShareholderForm"
            @update:open="showShareholderForm = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/shareholders`"
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showShareholderForm = false;
                            setActiveTab('shareholders');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Add shareholder') }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <Input name="name" required :placeholder="t('Full name')" />
                        <InputError :message="errors.name" />
                        <Input name="phone" :placeholder="t('Phone')" />
                        <Input name="email" type="email" :placeholder="t('Email')" />
                        <Input
                            name="share_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                            :placeholder="t('Share %')"
                        />
                        <InputError :message="errors.share_percent" />
                        <Input
                            name="invested_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('Initial capital (AFN)')"
                        />
                        <input type="hidden" name="currency" value="AFN" />
                        <Input name="transaction_date" type="date" />
                        <Textarea name="notes" rows="2" :placeholder="t('Notes')" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showShareholderForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Add shareholder') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="shareholderAction !== null"
            @update:open="(open) => !open && (shareholderAction = null)"
        >
            <DialogContent v-if="shareholderAction">
                <Form
                    :action="
                        shareholderAction.type === 'contribute'
                            ? `/mis/projects/${project.id}/shareholders/${shareholderAction.id}/contribute`
                            : `/mis/projects/${project.id}/shareholders/${shareholderAction.id}/distribute`
                    "
                    method="post"
                    :options="{ preserveScroll: true, resetOnSuccess: true }"
                    v-slot="{ errors, processing }"
                    @success="shareholderAction = null"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                shareholderAction.type === 'contribute'
                                    ? t('Add capital')
                                    : t('Return share')
                            }}
                        </DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label>
                                {{
                                    shareholderAction.type === 'contribute'
                                        ? t('Amount')
                                        : t('Amount to return')
                                }}
                                *
                            </Label>
                            <Input name="amount" type="number" min="0.01" step="0.01" required />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('Date') }}</Label>
                            <Input name="transaction_date" type="date" />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="shareholderAction = null">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{
                                shareholderAction.type === 'contribute'
                                    ? t('Record')
                                    : t('Return')
                            }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog :open="showReportForm" @update:open="showReportForm = $event">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/issues`"
                    method="post"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showReportForm = false;
                            setActiveTab('issues');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('New report') }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <Input name="title" :placeholder="t('Brief title')" required />
                        <InputError :message="errors.title" />
                        <select name="category" class="h-9 rounded-md border border-input px-3 text-sm">
                            <option value="">{{ t('Incident type') }}</option>
                            <option value="security">{{ t('Security incident') }}</option>
                            <option value="personnel">{{ t('Personnel / casualty') }}</option>
                            <option value="equipment">{{ t('Equipment failure') }}</option>
                            <option value="client">{{ t('Client complaint') }}</option>
                            <option value="operational">{{ t('Operational issue') }}</option>
                            <option value="other">{{ t('Other') }}</option>
                        </select>
                        <Textarea
                            name="description"
                            rows="4"
                            :placeholder="t('What happened? e.g. guard killed, vehicle breakdown, client dispute...')"
                            required
                        />
                        <InputError :message="errors.description" />
                        <select
                            name="severity"
                            class="h-9 rounded-md border border-input px-3 text-sm"
                        >
                            <option value="low">{{ t('Low') }}</option>
                            <option value="medium" selected>{{ t('Medium') }}</option>
                            <option value="high">{{ t('High') }}</option>
                            <option value="critical">{{ t('Critical') }}</option>
                        </select>
                        <InputError :message="errors.severity" />
                        <OptionalAttachmentField :error="errors.attachment" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showReportForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Submit report') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showAttachmentForm"
            @update:open="showAttachmentForm = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ processing }"
                    @success="showAttachmentForm = false"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Add attachment') }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-3 py-4">
                        <OptionalAttachmentField />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="secondary" @click="showAttachmentForm = false">
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">{{ t('Upload') }}</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="returningIssue !== null"
            @update:open="(open) => !open && closeReturnDialog()"
        >
            <DialogContent v-if="returningIssue">
                <Form
                    :action="`/mis/projects/${project.id}/equipment-issues/${returningIssue.id}/return`"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="
                        () => {
                            closeReturnDialog();
                            setActiveTab('equipment');
                        }
                    "
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Return to stock') }}</DialogTitle>
                        <DialogDescription>
                            {{ returningIssue.equipment_catalog?.name ?? t('Item') }}
                            · {{ outstandingForReturn }} {{ t('on site') }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="return-quantity">{{ t('Quantity') }}</Label>
                            <Input
                                id="return-quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                :max="outstandingForReturn"
                                :default-value="outstandingForReturn"
                                required
                            />
                            <InputError :message="errors.quantity" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="return-returned-at">{{ t('Returned at') }}</Label>
                            <Input
                                id="return-returned-at"
                                name="returned_at"
                                type="date"
                                :default-value="today"
                            />
                            <InputError :message="errors.returned_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="return-notes">{{ t('Notes') }}</Label>
                            <Textarea
                                id="return-notes"
                                name="notes"
                                rows="2"
                                :placeholder="t('Notes')"
                            />
                            <InputError :message="errors.notes" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeReturnDialog"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Submit Return') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showIncomeForm"
            @update:open="showIncomeForm = $event"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/incomes`"
                    method="post"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showIncomeForm = false;
                            setActiveTab('finance');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Record payment') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <Input
                            name="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('Amount (AFN)')"
                            required
                        />
                        <InputError :message="errors.amount" />
                        <Input
                            name="transaction_date"
                            type="date"
                            :default-value="today"
                            required
                        />
                        <InputError :message="errors.transaction_date" />
                        <FinanceCategoryField
                            applies-to="income"
                            :categories="financeCategories ?? []"
                            :error="errors.category"
                        />
                        <Textarea
                            name="description"
                            rows="3"
                            :placeholder="t('Payment note (optional)')"
                        />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" value="AFN" />
                        <OptionalAttachmentField
                            :label="t('Receipt')"
                            :error="errors.attachment"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showIncomeForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Record payment') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showExpenseForm"
            @update:open="showExpenseForm = $event"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    :action="`/mis/projects/${project.id}/expenses`"
                    method="post"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="
                        () => {
                            showExpenseForm = false;
                            setActiveTab('finance');
                        }
                    "
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Add expense') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <Input
                            name="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('Amount (AFN)')"
                            required
                        />
                        <InputError :message="errors.amount" />
                        <Input
                            name="transaction_date"
                            type="date"
                            :default-value="today"
                            required
                        />
                        <InputError :message="errors.transaction_date" />
                        <FinanceCategoryField
                            applies-to="expense"
                            :categories="financeCategories ?? []"
                            :error="errors.category"
                        />
                        <div class="flex items-start gap-2">
                            <input
                                type="hidden"
                                name="paid_from_cash_box"
                                value="0"
                            />
                            <input
                                id="project-expense-cash-box"
                                name="paid_from_cash_box"
                                type="checkbox"
                                value="1"
                                class="mt-1 size-4 rounded border"
                            />
                            <div>
                                <Label for="project-expense-cash-box">{{
                                    t('Spend from cash box')
                                }}</Label>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatAfn(cashBox?.remaining) }}
                                    {{ t('left') }}
                                </p>
                                <InputError :message="errors.paid_from_cash_box" />
                            </div>
                        </div>
                        <Textarea
                            name="description"
                            rows="3"
                            :placeholder="t('Description')"
                        />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" value="AFN" />
                        <OptionalAttachmentField
                            :label="t('Receipt')"
                            :error="errors.attachment"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="showExpenseForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Add expense') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editingFinance !== null"
            @update:open="(open) => !open && closeFinanceEdit()"
        >
            <DialogContent v-if="editingFinance">
                <Form
                    :action="
                        editingFinance.type === 'income'
                            ? `/finance/incomes/${editingFinance.row.id}`
                            : `/finance/expenses/${editingFinance.row.id}`
                    "
                    method="put"
                    @success="
                        () => {
                            closeFinanceEdit();
                            setActiveTab('finance');
                        }
                    "
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingFinance.type === 'income'
                                    ? t('Edit payment')
                                    : t('Edit expense')
                            }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-finance-amount">{{ t('Amount (AFN)') }}</Label>
                            <Input
                                id="edit-finance-amount"
                                name="amount"
                                type="number"
                                min="0"
                                step="0.01"
                                :default-value="editingFinance.row.amount"
                                required
                            />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-finance-date">{{ t('Date') }}</Label>
                            <Input
                                id="edit-finance-date"
                                name="transaction_date"
                                type="date"
                                :default-value="
                                    editingFinance.row.transaction_date?.slice(
                                        0,
                                        10,
                                    ) ?? ''
                                "
                                required
                            />
                            <InputError :message="errors.transaction_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-finance-description">{{ t('Description') }}</Label>
                            <Input
                                id="edit-finance-description"
                                name="description"
                                :default-value="editingFinance.row.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <FinanceCategoryField
                            :applies-to="editingFinance.type === 'income' ? 'income' : 'expense'"
                            :categories="financeCategories ?? []"
                            :default-value="editingFinance.row.category"
                            :error="errors.category"
                            :manage="false"
                        />
                        <div
                            v-if="editingFinance.type === 'expense'"
                            class="flex items-start gap-2"
                        >
                            <input
                                type="hidden"
                                name="paid_from_cash_box"
                                value="0"
                            />
                            <input
                                id="edit-expense-cash-box"
                                name="paid_from_cash_box"
                                type="checkbox"
                                value="1"
                                class="mt-1 size-4 rounded border"
                                :checked="!!editingFinance.row.paid_from_cash_box"
                            />
                            <div>
                                <Label for="edit-expense-cash-box">{{
                                    t('Spend from cash box')
                                }}</Label>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatAfn(cashBox?.remaining) }}
                                    {{ t('left') }}
                                </p>
                                <InputError :message="errors.paid_from_cash_box" />
                            </div>
                        </div>
                        <input
                            type="hidden"
                            name="currency"
                            value="AFN"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeFinanceEdit"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save changes') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editingIssue !== null"
            @update:open="(open) => !open && closeIssueEdit()"
        >
            <DialogContent v-if="editingIssue">
                <Form
                    :action="`/mis/projects/${project.id}/issues/${editingIssue.id}`"
                    method="put"
                    @success="
                        () => {
                            closeIssueEdit();
                            setActiveTab('issues');
                        }
                    "
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Edit issue') }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-issue-title">{{ t('Title') }}</Label>
                            <Input
                                id="edit-issue-title"
                                name="title"
                                :default-value="editingIssue.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-description">{{ t('What happened') }}</Label>
                            <Textarea
                                id="edit-issue-description"
                                name="description"
                                rows="4"
                                :default-value="editingIssue.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-resolution">{{ t('How we fixed it') }}</Label>
                            <Textarea
                                id="edit-issue-resolution"
                                name="resolution_notes"
                                rows="4"
                                :placeholder="t('Describe the resolution and actions taken')"
                                :default-value="editingIssue.resolution_notes ?? ''"
                            />
                            <InputError :message="errors.resolution_notes" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-severity">{{ t('Severity') }}</Label>
                            <select
                                id="edit-issue-severity"
                                name="severity"
                                class="h-9 rounded-md border border-input px-3 text-sm"
                            >
                                <option
                                    value="low"
                                    :selected="editingIssue.severity === 'low'"
                                >
                                    Low
                                </option>
                                <option
                                    value="medium"
                                    :selected="editingIssue.severity === 'medium'"
                                >
                                    Medium
                                </option>
                                <option
                                    value="high"
                                    :selected="editingIssue.severity === 'high'"
                                >
                                    High
                                </option>
                                <option
                                    value="critical"
                                    :selected="editingIssue.severity === 'critical'"
                                >
                                    Critical
                                </option>
                            </select>
                            <InputError :message="errors.severity" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-status">{{ t('Status') }}</Label>
                            <select
                                id="edit-issue-status"
                                name="status"
                                class="h-9 rounded-md border border-input px-3 text-sm"
                            >
                                <option
                                    value="open"
                                    :selected="editingIssue.status === 'open'"
                                >
                                    {{ t('Open') }}
                                </option>
                                <option
                                    value="in_progress"
                                    :selected="editingIssue.status === 'in_progress'"
                                >
                                    {{ t('In progress') }}
                                </option>
                                <option
                                    value="resolved"
                                    :selected="editingIssue.status === 'resolved'"
                                >
                                    {{ t('Resolved') }}
                                </option>
                                <option
                                    value="closed"
                                    :selected="editingIssue.status === 'closed'"
                                >
                                    {{ t('Closed') }}
                                </option>
                            </select>
                            <InputError :message="errors.status" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeIssueEdit"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save changes') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="showProjectEditForm"
            @update:open="onProjectEditOpenChange"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    class="grid gap-4 py-2"
                    :options="{ preserveScroll: true, forceFormData: true }"
                    validate-files
                    v-slot="{ errors, processing }"
                    @success="closeProjectEdit"
                >
                    <DialogHeader>
                        <DialogTitle>{{ t('Edit project') }}</DialogTitle>
                        <DialogDescription>
                            {{ project.code }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-project-org">{{ t('Organization') }} *</Label>
                        <select
                            id="edit-project-org"
                            name="organization_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="org in organizations ?? []"
                                :key="org.id"
                                :value="org.id"
                                :selected="project.organization?.id === org.id"
                            >
                                {{ org.name }}
                            </option>
                        </select>
                        <InputError :message="errors.organization_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-name">{{ t('Project / opportunity title') }} *</Label>
                        <Input
                            id="edit-project-name"
                            name="name"
                            required
                            :default-value="project.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-reference">{{ t('Reference #') }}</Label>
                        <Input
                            id="edit-project-reference"
                            name="reference_number"
                            :default-value="project.reference_number ?? ''"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-deadline">{{ t('Submission deadline') }}</Label>
                        <Input
                            id="edit-project-deadline"
                            name="submission_deadline"
                            type="date"
                            :default-value="project.submission_deadline?.slice(0, 10) ?? ''"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-location">{{ t('Location') }}</Label>
                        <Input
                            id="edit-project-location"
                            name="location"
                            :default-value="project.location ?? ''"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-source">{{ t('Source') }}</Label>
                        <Input
                            id="edit-project-source"
                            name="source"
                            :default-value="project.source ?? ''"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-project-bid">{{ t('Our bid amount (AFN)') }}</Label>
                        <Input
                            id="edit-project-bid"
                            name="our_bid_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :default-value="project.our_bid_amount ?? ''"
                        />
                        <input type="hidden" name="currency" value="AFN" />
                    </div>

                    <SecurityScopeField
                        :selected="project.security_scope"
                        :error="errors.security_scope"
                        include-marker
                    />

                    <div class="grid gap-2">
                        <Label for="edit-project-scope">{{ t('Scope summary') }}</Label>
                        <textarea
                            id="edit-project-scope"
                            name="scope_summary"
                            rows="3"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            :default-value="project.scope_summary ?? ''"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeProjectEdit"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('Save changes') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
