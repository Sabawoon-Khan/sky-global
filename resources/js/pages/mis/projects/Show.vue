<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Paperclip, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
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

interface FinanceRow {
    id: number;
    amount: number;
    currency: string;
    description: string | null;
    transaction_date: string;
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
    start_date: string | null;
    end_date: string | null;
    monthly_rate: number | null;
    currency: string | null;
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

interface Project {
    id: number;
    code: string;
    name: string;
    reference_number: string | null;
    status: string;
    scope_summary: string | null;
    location: string | null;
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
    employees?: PersonOption[];
    contractors?: PersonOption[];
    currencies?: string[];
    stockItems?: StockItemOption[];
}>();

const { t, can, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: '/projects' },
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

const initialTab = (): TabId => {
    const fromUrl = new URLSearchParams(window.location.search).get('tab');

    return tabIds.includes(fromUrl as TabId) ? (fromUrl as TabId) : 'overview';
};

const activeTab = ref<TabId>(initialTab());

const setActiveTab = (tab: TabId): void => {
    activeTab.value = tab;

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
};

const today = new Date().toISOString().slice(0, 10);

const editingFinance = ref<{
    row: FinanceRow;
    type: 'income' | 'expense';
} | null>(null);

const editingIssue = ref<ProjectIssue | null>(null);

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
    router.post(`/projects/${props.project.id}/status`, { status }, { preserveScroll: true });
};

const markLost = () => {
    router.post(
        `/projects/${props.project.id}/status`,
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
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'in_progress' },
        });
    }

    if (['open', 'in_progress'].includes(issue.status)) {
        actions.push({
            label: t('Resolve'),
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'resolved' },
        });
    }

    if (issue.status === 'resolved') {
        actions.push({
            label: t('Close'),
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'closed' },
        });
    }

    actions.push({
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/projects/${props.project.id}/issues/${issue.id}`,
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

const closeFinanceEdit = (): void => {
    editingFinance.value = null;
};

const closeIssueEdit = (): void => {
    editingIssue.value = null;
};
</script>

<template>
    <Head :title="project.name" />

    <MisPage>
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="font-mono text-xs text-muted-foreground">{{ project.code }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Badge :variant="statusVariant(project.status)">{{ project.status }}</Badge>
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
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link href="/projects">{{ t('Back to list') }}</Link>
                </Button>
                <template v-if="can('projects.edit') && statusOptions.length">
                    <Button
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        size="sm"
                        :variant="opt.value === 'lost' ? 'destructive' : 'default'"
                        @click="opt.value === 'lost' ? markLost() : changeStatus(opt.value)"
                    >
                        Mark {{ opt.label }}
                    </Button>
                </template>
            </div>
        </div>

        <!-- Quick stats -->
        <div class="grid gap-3 sm:grid-cols-4">
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">{{ t('Our bid') }}</p>
                    <p class="text-lg font-semibold">
                        {{ formatAfn(project.our_bid_amount) }}
                    </p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">{{ t('Deadline') }}</p>
                    <p class="text-lg font-semibold">
                        {{ formatDate(project.submission_deadline) }}
                    </p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">{{ t('Competitors') }}</p>
                    <p class="text-lg font-semibold">{{ project.competitor_bids.length }}</p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">{{ t('Margin') }}</p>
                    <p class="text-lg font-semibold">
                        {{ formatAfn(finance.margin) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 overflow-x-auto border-b pb-0">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="shrink-0 border-b-2 px-3 py-2 text-sm transition-colors"
                :class="
                    activeTab === tab.id
                        ? 'border-primary font-medium text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
                @click="setActiveTab(tab.id)"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Overview -->
        <div v-if="activeTab === 'overview'" class="grid gap-3 lg:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Project info') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Location') }}</span>
                        <span>{{ project.location ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">{{ t('Scope') }}</span>
                        <span class="text-right">
                            <template v-if="projectScopeTypes.length">
                                {{ projectScopeTypes.map(formatScopeType).join(', ') }}
                            </template>
                            <template v-else>—</template>
                        </span>
                    </div>
                    <div v-if="project.scope_summary" class="pt-2">
                        <p class="text-muted-foreground">{{ t('Summary') }}</p>
                        <p class="mt-1">{{ project.scope_summary }}</p>
                    </div>
                </CardContent>
            </Card>
            <Card v-if="!isBiddingPhase || project.status === 'won'">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Contract (when won)') }}</CardTitle>
                </CardHeader>
                <CardContent>
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
                </CardContent>
            </Card>
            <Card v-if="project.status === 'lost'" class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base text-destructive">{{ t('Loss details') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p v-if="project.loss_reason">{{ project.loss_reason }}</p>
                    <p v-if="project.winning_competitor_name">
                        {{ t('Winner:') }} {{ project.winning_competitor_name }}
                        ({{ formatAfn(project.winning_amount) }})
                    </p>
                </CardContent>
            </Card>
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
        <div v-else-if="activeTab === 'competitors'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
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
                                    @click="router.delete(`/projects/${project.id}/competitors/${comp.id}`, { preserveScroll: true })"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Can permission="bidding.view_competitors">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Add competitor') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/competitors`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        validate-files
                        v-slot="{ processing }"
                    >
                        <Input name="competitor_name" :placeholder="t('Company name')" required />
                        <Input name="bid_amount" type="number" min="0" step="0.01" :placeholder="t('Amount (AFN)')" />
                        <input type="hidden" name="currency" value="AFN" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_estimated" value="1" />
                            Estimated price
                        </label>
                        <OptionalAttachmentField />
                        <Button type="submit" size="sm" :disabled="processing">{{ t('Add') }}</Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
        </div>

        <!-- Personnel -->
        <div v-else-if="activeTab === 'personnel'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
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
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ deployment.personnel_type.includes('Employee') ? t('Employee') : t('Contractor') }}
                                    #{{ deployment.personnel_id }}
                                </p>
                                <p v-if="deployment.role" class="text-sm text-muted-foreground">
                                    {{ deployment.role }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ deployment.start_date ?? '—' }} — {{ deployment.end_date ?? t('Ongoing') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="deployment.monthly_rate" class="text-sm font-medium">
                                    {{ formatAfn(deployment.monthly_rate) }}/mo
                                </span>
                                <Can permission="projects.delete">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="
                                        router.delete(
                                            `/projects/${project.id}/deployments/${deployment.id}`,
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
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Assign person') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/deployments`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('personnel')"
                    >
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
                        <Button type="submit" size="sm" :disabled="processing">{{ t('Assign') }}</Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
        </div>

        <!-- Equipment from stock -->
        <div v-else-if="activeTab === 'equipment'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Equipment on this project') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!project.equipment_issues?.length"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No stock items issued to this project yet.') }}
                    </div>
                    <div v-else class="overflow-x-auto rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 text-start font-medium">{{ t('Item') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Issued') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Returned') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('On site') }}</th>
                                    <th class="px-3 py-2 text-end font-medium">{{ t('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="issue in project.equipment_issues"
                                    :key="issue.id"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2">
                                        <p class="font-medium">
                                            {{ issue.equipment_catalog?.name ?? t('Item') }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(issue.issued_at) }}
                                            <span v-if="issue.equipment_catalog?.category">
                                                · {{ issue.equipment_catalog.category }}
                                            </span>
                                        </p>
                                    </td>
                                    <td class="px-3 py-2 text-end tabular-nums">{{ issue.quantity }}</td>
                                    <td class="px-3 py-2 text-end tabular-nums">{{ issue.quantity_returned }}</td>
                                    <td class="px-3 py-2 text-end font-semibold tabular-nums">
                                        {{ issue.quantity - issue.quantity_returned }}
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <Can permission="inventory.edit">
                                            <Form
                                                v-if="issue.quantity - issue.quantity_returned > 0"
                                                :action="`/projects/${project.id}/equipment-issues/${issue.id}/return`"
                                                method="post"
                                                class="inline-flex items-center gap-1"
                                                :options="{ preserveScroll: true }"
                                                v-slot="{ processing }"
                                            >
                                                <Input
                                                    name="quantity"
                                                    type="number"
                                                    min="1"
                                                    :max="issue.quantity - issue.quantity_returned"
                                                    :value="issue.quantity - issue.quantity_returned"
                                                    class="h-8 w-20"
                                                    required
                                                />
                                                <Button type="submit" size="sm" variant="outline" :disabled="processing">
                                                    {{ t('Return') }}
                                                </Button>
                                            </Form>
                                        </Can>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
            <Can permission="inventory.create">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('Issue from stock') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            :action="`/projects/${project.id}/equipment-issues`"
                            method="post"
                            class="grid gap-2"
                            :options="{ preserveScroll: true, resetOnSuccess: true }"
                            v-slot="{ errors, processing }"
                            @success="setActiveTab('equipment')"
                        >
                            <select
                                name="equipment_catalog_id"
                                required
                                class="h-9 rounded-md border border-input px-3 text-sm"
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
                            <Input name="quantity" type="number" min="1" required :placeholder="t('Quantity')" />
                            <InputError :message="errors.quantity" />
                            <Input name="issued_at" type="date" />
                            <Textarea name="notes" rows="2" :placeholder="t('Notes')" />
                            <Button type="submit" size="sm" :disabled="processing">
                                {{ t('Issue to project') }}
                            </Button>
                            <p class="text-xs text-muted-foreground">
                                {{ t('Stock is reduced from the depot when you issue items.') }}
                            </p>
                        </Form>
                    </CardContent>
                </Card>
            </Can>
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
                                                `/projects/${project.id}/shareholders/${shareholder.id}`,
                                                { preserveScroll: true },
                                            )
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </Can>
                            </div>
                            <Form
                                v-if="
                                    shareholderAction?.id === shareholder.id &&
                                    shareholderAction.type === 'contribute'
                                "
                                :action="`/projects/${project.id}/shareholders/${shareholder.id}/contribute`"
                                method="post"
                                class="mt-3 grid gap-2 rounded-md bg-muted/30 p-3 sm:grid-cols-3"
                                :options="{ preserveScroll: true, resetOnSuccess: true }"
                                v-slot="{ errors, processing }"
                                @success="shareholderAction = null"
                            >
                                <div class="grid gap-1">
                                    <Label>{{ t('Amount') }} *</Label>
                                    <Input name="amount" type="number" min="0.01" step="0.01" required />
                                    <InputError :message="errors.amount" />
                                </div>
                                <div class="grid gap-1">
                                    <Label>{{ t('Date') }}</Label>
                                    <Input name="transaction_date" type="date" />
                                </div>
                                <div class="flex items-end">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Record') }}
                                    </Button>
                                </div>
                            </Form>
                            <Form
                                v-if="
                                    shareholderAction?.id === shareholder.id &&
                                    shareholderAction.type === 'distribute'
                                "
                                :action="`/projects/${project.id}/shareholders/${shareholder.id}/distribute`"
                                method="post"
                                class="mt-3 grid gap-2 rounded-md bg-muted/30 p-3 sm:grid-cols-3"
                                :options="{ preserveScroll: true, resetOnSuccess: true }"
                                v-slot="{ errors, processing }"
                                @success="shareholderAction = null"
                            >
                                <div class="grid gap-1">
                                    <Label>{{ t('Amount to return') }} *</Label>
                                    <Input name="amount" type="number" min="0.01" step="0.01" required />
                                    <InputError :message="errors.amount" />
                                </div>
                                <div class="grid gap-1">
                                    <Label>{{ t('Date') }}</Label>
                                    <Input name="transaction_date" type="date" />
                                </div>
                                <div class="flex items-end">
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ t('Return') }}
                                    </Button>
                                </div>
                            </Form>
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
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base">{{ t('Add shareholder') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                :action="`/projects/${project.id}/shareholders`"
                                method="post"
                                class="grid gap-2"
                                :options="{ preserveScroll: true, resetOnSuccess: true }"
                                v-slot="{ errors, processing }"
                                @success="setActiveTab('shareholders')"
                            >
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
                                <Button type="submit" size="sm" :disabled="processing">
                                    {{ t('Add shareholder') }}
                                </Button>
                            </Form>
                        </CardContent>
                    </Card>
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
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Record payment') }}</CardTitle>
                    <p class="text-xs text-muted-foreground">
                        {{ t('Log a client payment received for this project (amount in AFN).') }}
                    </p>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/incomes`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        validate-files
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('finance')"
                    >
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
                        <Textarea name="description" rows="3" :placeholder="t('Payment note (optional)')" />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" value="AFN" />
                        <OptionalAttachmentField :label="t('Receipt')" :error="errors.attachment" />
                        <Button type="submit" size="sm" :disabled="processing">
                            {{ t('Record payment') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>

            <Can permission="finance.create">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Add expense') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/expenses`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        validate-files
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('finance')"
                    >
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
                        <Textarea name="description" rows="3" :placeholder="t('Description')" />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" value="AFN" />
                        <OptionalAttachmentField :label="t('Receipt')" :error="errors.attachment" />
                        <Button type="submit" size="sm" :disabled="processing">
                            Record expense
                        </Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>

            <Card>
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
                                <a
                                    v-for="file in row.attachments ?? []"
                                    :key="file.id"
                                    :href="file.download_url"
                                    class="ms-2 inline-flex items-center gap-0.5 text-primary hover:underline"
                                >
                                    <Paperclip class="size-3" />
                                    {{ file.original_filename }}
                                </a>
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
                                <a
                                    v-for="file in row.attachments ?? []"
                                    :key="file.id"
                                    :href="file.download_url"
                                    class="ms-2 inline-flex items-center gap-0.5 text-primary hover:underline"
                                >
                                    <Paperclip class="size-3" />
                                    {{ file.original_filename }}
                                </a>
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
        <div v-else-if="activeTab === 'issues'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
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
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('New report') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/issues`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        validate-files
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('issues')"
                    >
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
                        <Button type="submit" size="sm" :disabled="processing">
                            {{ t('Submit report') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
        </div>

        <!-- Attachments -->
        <div v-else-if="activeTab === 'attachments'" class="grid gap-3 lg:grid-cols-3">
            <EntityAttachments
                class="lg:col-span-2"
                :attachments="project.attachments"
            />
            <Can permission="projects.edit">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('Add attachment') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="ProjectController.update.form(project.id)"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        validate-files
                        v-slot="{ processing }"
                    >
                        <OptionalAttachmentField />
                        <Button type="submit" size="sm" :disabled="processing">{{ t('Upload') }}</Button>
                    </Form>
                </CardContent>
            </Card>
            </Can>
        </div>

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
                                :default-value="editingFinance.row.transaction_date.slice(0, 10)"
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
                    :action="`/projects/${project.id}/issues/${editingIssue.id}`"
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
    </MisPage>
</template>
