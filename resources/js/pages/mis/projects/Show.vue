<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
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
import ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import { formatCurrency, formatDate } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

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

interface FinanceRow {
    id: number;
    amount: number;
    currency: string;
    description: string | null;
    transaction_date: string;
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
    opened_at: string | null;
}

interface Project {
    id: number;
    code: string;
    name: string;
    reference_number: string | null;
    status: string;
    scope_summary: string | null;
    location: string | null;
    security_scope: string | null;
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
    };
    statusOptions: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: '/projects' },
            { title: 'Project', href: '#' },
        ],
    },
});

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'bid', label: 'Our Bid' },
    { id: 'competitors', label: 'Competitors' },
    { id: 'finance', label: 'Finance' },
    { id: 'activity', label: 'Activity' },
    { id: 'issues', label: 'Issues' },
    { id: 'attachments', label: 'Attachments' },
] as const;

type TabId = (typeof tabs)[number]['id'];

const tabIds = tabs.map((tab) => tab.id);

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
): RowActionItem[] => [
    {
        label: 'Edit',
        icon: Pencil,
        onClick: () => {
            editingFinance.value = { row, type };
        },
    },
    {
        label: 'Delete',
        icon: Trash2,
        variant: 'destructive',
        href:
            type === 'income'
                ? `/finance/incomes/${row.id}`
                : `/finance/expenses/${row.id}`,
        method: 'delete',
        confirm: {
            title: `Delete ${type}`,
            description: `Delete "${row.description ?? type}"? This cannot be undone.`,
            confirmLabel: 'Delete',
        },
    },
];

const issueActions = (issue: ProjectIssue): RowActionItem[] => {
    const actions: RowActionItem[] = [
        {
            label: 'Edit',
            icon: Pencil,
            onClick: () => {
                editingIssue.value = issue;
            },
        },
    ];

    if (issue.status === 'open') {
        actions.push({
            label: 'Mark in progress',
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'in_progress' },
        });
    }

    if (['open', 'in_progress'].includes(issue.status)) {
        actions.push({
            label: 'Resolve',
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'resolved' },
        });
    }

    if (issue.status === 'resolved') {
        actions.push({
            label: 'Close',
            href: `/projects/${props.project.id}/issues/${issue.id}`,
            method: 'put',
            data: { status: 'closed' },
        });
    }

    actions.push({
        label: 'Delete',
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/projects/${props.project.id}/issues/${issue.id}`,
        method: 'delete',
        confirm: {
            title: 'Delete issue',
            description: `Delete "${issue.title}"? This cannot be undone.`,
            confirmLabel: 'Delete',
        },
    });

    return actions;
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
                <Heading :title="project.name" :description="project.reference_number ?? undefined" />
                <div class="mt-2 flex flex-wrap gap-2">
                    <Badge :variant="statusVariant(project.status)">{{ project.status }}</Badge>
                    <Badge v-if="project.organization" variant="secondary">
                        {{ project.organization.name }}
                    </Badge>
                    <Badge v-if="project.security_scope" variant="outline">
                        {{ project.security_scope }}
                    </Badge>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link href="/projects">Back to list</Link>
                </Button>
                <template v-if="statusOptions.length">
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
                    <p class="text-xs text-muted-foreground">Our bid</p>
                    <p class="text-lg font-semibold">
                        {{ formatCurrency(project.our_bid_amount, project.currency) }}
                    </p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">Deadline</p>
                    <p class="text-lg font-semibold">
                        {{ formatDate(project.submission_deadline) }}
                    </p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">Competitors</p>
                    <p class="text-lg font-semibold">{{ project.competitor_bids.length }}</p>
                </CardContent>
            </Card>
            <Card class="py-0">
                <CardContent class="p-3">
                    <p class="text-xs text-muted-foreground">Margin</p>
                    <p class="text-lg font-semibold">
                        {{ formatCurrency(finance.margin, finance.currency) }}
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
                    <CardTitle class="text-base">Project info</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Location</span>
                        <span>{{ project.location ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Scope</span>
                        <span class="text-right">{{ project.security_scope ?? '—' }}</span>
                    </div>
                    <div v-if="project.scope_summary" class="pt-2">
                        <p class="text-muted-foreground">Summary</p>
                        <p class="mt-1">{{ project.scope_summary }}</p>
                    </div>
                </CardContent>
            </Card>
            <Card v-if="!isBiddingPhase || project.status === 'won'">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Contract (when won)</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Contract value</span>
                        <span>{{ formatCurrency(project.total_contract_value, project.currency) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Period</span>
                        <span>{{ formatDate(project.contract_start) }} – {{ formatDate(project.contract_end) }}</span>
                    </div>
                </CardContent>
            </Card>
            <Card v-if="project.status === 'lost'" class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base text-destructive">Loss details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p v-if="project.loss_reason">{{ project.loss_reason }}</p>
                    <p v-if="project.winning_competitor_name">
                        Winner: {{ project.winning_competitor_name }}
                        ({{ formatCurrency(project.winning_amount, project.currency) }})
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Our Bid -->
        <Card v-else-if="activeTab === 'bid'">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Our bid details</CardTitle>
                <CardDescription>Update pricing and scope — saved on this project only</CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    class="grid gap-3 sm:grid-cols-2"
                    v-slot="{ errors, processing }"
                    :options="{ preserveScroll: true }"
                >
                    <div class="grid gap-1.5">
                        <Label for="our_bid_amount">Our bid amount</Label>
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
                    <div class="grid gap-1.5">
                        <Label for="currency">Currency</Label>
                        <select
                            id="currency"
                            name="currency"
                            class="flex h-9 w-full rounded-md border border-input px-3 text-sm"
                        >
                            <option value="USD" :selected="project.currency === 'USD'">USD</option>
                            <option value="AFN" :selected="project.currency === 'AFN'">AFN</option>
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="submission_deadline">Deadline</Label>
                        <Input
                            id="submission_deadline"
                            name="submission_deadline"
                            type="date"
                            :default-value="project.submission_deadline?.slice(0, 10) ?? ''"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="reference_number">Reference #</Label>
                        <Input
                            id="reference_number"
                            name="reference_number"
                            :default-value="project.reference_number ?? ''"
                        />
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="scope_summary">Scope summary</Label>
                        <textarea
                            id="scope_summary"
                            name="scope_summary"
                            rows="3"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            :default-value="project.scope_summary ?? ''"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" size="sm" :disabled="processing">Save bid details</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <!-- Competitors -->
        <div v-else-if="activeTab === 'competitors'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Other bidders (optional)</CardTitle>
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
                                    {{ formatCurrency(comp.bid_amount, comp.currency) }}
                                    <span v-if="comp.is_estimated"> (estimated)</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge v-if="comp.is_winner" variant="secondary">Winner</Badge>
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
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Add competitor</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/competitors`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ processing }"
                    >
                        <Input name="competitor_name" placeholder="Company name" required />
                        <Input name="bid_amount" type="number" min="0" step="0.01" placeholder="Amount" />
                        <select name="currency" class="h-9 rounded-md border border-input px-3 text-sm">
                            <option value="USD">USD</option>
                            <option value="AFN">AFN</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_estimated" value="1" />
                            Estimated price
                        </label>
                        <OptionalAttachmentField />
                        <Button type="submit" size="sm" :disabled="processing">Add</Button>
                    </Form>
                </CardContent>
            </Card>
        </div>

        <!-- Finance -->
        <div v-else-if="activeTab === 'finance'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-3">
                <CardContent class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">Income</p>
                        <p class="text-xl font-bold">{{ formatCurrency(finance.income, finance.currency) }}</p>
                    </div>
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">Expenses</p>
                        <p class="text-xl font-bold">{{ formatCurrency(finance.expense, finance.currency) }}</p>
                    </div>
                    <div class="rounded-md border p-3">
                        <p class="text-xs text-muted-foreground">Margin</p>
                        <p class="text-xl font-bold">{{ formatCurrency(finance.margin, finance.currency) }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Add income</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/incomes`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('finance')"
                    >
                        <Input
                            name="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Amount"
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
                        <Input name="description" placeholder="Description" />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" :value="project.currency" />
                        <OptionalAttachmentField label="Receipt" :error="errors.attachment" />
                        <Button type="submit" size="sm" :disabled="processing">
                            Record payment
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Add expense</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/expenses`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('finance')"
                    >
                        <Input
                            name="amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Amount"
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
                        <Input name="description" placeholder="Description" />
                        <InputError :message="errors.description" />
                        <input type="hidden" name="currency" :value="project.currency" />
                        <OptionalAttachmentField label="Receipt" :error="errors.attachment" />
                        <Button type="submit" size="sm" :disabled="processing">
                            Record expense
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Recent entries</CardTitle>
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
                                + {{ row.description ?? 'Income' }}
                            </span>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(row.transaction_date) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span>{{ formatCurrency(row.amount, row.currency) }}</span>
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
                                − {{ row.description ?? 'Expense' }}
                            </span>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(row.transaction_date) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span>{{ formatCurrency(row.amount, row.currency) }}</span>
                            <RowActionsMenu :actions="financeEntryActions(row, 'expense')" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Activity -->
        <Card v-else-if="activeTab === 'activity'">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Activity timeline</CardTitle>
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

        <!-- Issues -->
        <div v-else-if="activeTab === 'issues'" class="grid gap-3 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Issues</CardTitle>
                    <CardDescription>Track problems and resolutions for this project</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!project.issues.length" class="py-6 text-center text-sm text-muted-foreground">
                        No issues reported yet.
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="issue in project.issues"
                            :key="issue.id"
                            class="flex items-start justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p class="font-medium">{{ issue.title }}</p>
                                <p
                                    v-if="issue.description"
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    {{ issue.description }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge variant="outline">{{ issue.status }}</Badge>
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
                                </div>
                            </div>
                            <RowActionsMenu :actions="issueActions(issue)" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Report issue</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="`/projects/${project.id}/issues`"
                        method="post"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ errors, processing }"
                        @success="setActiveTab('issues')"
                    >
                        <Input name="title" placeholder="Issue title" required />
                        <InputError :message="errors.title" />
                        <textarea
                            name="description"
                            rows="3"
                            placeholder="Description"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                        <InputError :message="errors.description" />
                        <select
                            name="severity"
                            class="h-9 rounded-md border border-input px-3 text-sm"
                        >
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                        <InputError :message="errors.severity" />
                        <OptionalAttachmentField :error="errors.attachment" />
                        <Button type="submit" size="sm" :disabled="processing">
                            Create issue
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </div>

        <!-- Attachments -->
        <div v-else-if="activeTab === 'attachments'" class="grid gap-3 lg:grid-cols-3">
            <EntityAttachments
                class="lg:col-span-2"
                :attachments="project.attachments"
            />
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Add attachment</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="ProjectController.update.form(project.id)"
                        class="grid gap-2"
                        :options="{ preserveScroll: true, forceFormData: true }"
                        v-slot="{ processing }"
                    >
                        <OptionalAttachmentField />
                        <Button type="submit" size="sm" :disabled="processing">Upload</Button>
                    </Form>
                </CardContent>
            </Card>
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
                            Edit {{ editingFinance.type }}
                        </DialogTitle>
                        <DialogDescription>
                            Update amount, date, or description.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-finance-amount">Amount</Label>
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
                            <Label for="edit-finance-date">Date</Label>
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
                            <Label for="edit-finance-description">Description</Label>
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
                            :value="editingFinance.row.currency"
                        />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="closeFinanceEdit"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Save changes
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
                        <DialogTitle>Edit issue</DialogTitle>
                        <DialogDescription>
                            Update issue details and status.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-issue-title">Title</Label>
                            <Input
                                id="edit-issue-title"
                                name="title"
                                :default-value="editingIssue.title"
                                required
                            />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-description">Description</Label>
                            <textarea
                                id="edit-issue-description"
                                name="description"
                                rows="3"
                                class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            >{{ editingIssue.description ?? '' }}</textarea>
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-issue-severity">Severity</Label>
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
                            <Label for="edit-issue-status">Status</Label>
                            <select
                                id="edit-issue-status"
                                name="status"
                                class="h-9 rounded-md border border-input px-3 text-sm"
                            >
                                <option
                                    value="open"
                                    :selected="editingIssue.status === 'open'"
                                >
                                    Open
                                </option>
                                <option
                                    value="in_progress"
                                    :selected="editingIssue.status === 'in_progress'"
                                >
                                    In progress
                                </option>
                                <option
                                    value="resolved"
                                    :selected="editingIssue.status === 'resolved'"
                                >
                                    Resolved
                                </option>
                                <option
                                    value="closed"
                                    :selected="editingIssue.status === 'closed'"
                                >
                                    Closed
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
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Save changes
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </MisPage>
</template>
