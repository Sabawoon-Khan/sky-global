<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import EntityAttachments, {
    type EntityAttachment,
} from '@/components/EntityAttachments.vue';
import InputError from '@/components/InputError.vue';
import MisPage from '@/components/MisPage.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ProjectController from '@/actions/App/Http/Controllers/Project/ProjectController';
import { formatCurrency, formatDate } from '@/lib/format';

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
    severity: string;
    status: string;
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
const activeTab = ref<TabId>('overview');

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
                @click="activeTab = tab.id"
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
                        v-slot="{ processing }"
                    >
                        <Input name="amount" type="number" min="0" step="0.01" placeholder="Amount" required />
                        <Input name="transaction_date" type="date" required />
                        <Input name="description" placeholder="Description" />
                        <OptionalAttachmentField label="Receipt" />
                        <Button type="submit" size="sm" :disabled="processing">Record payment</Button>
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
                        v-slot="{ processing }"
                    >
                        <Input name="amount" type="number" min="0" step="0.01" placeholder="Amount" required />
                        <Input name="transaction_date" type="date" required />
                        <Input name="description" placeholder="Description" />
                        <OptionalAttachmentField label="Receipt" />
                        <Button type="submit" size="sm" :disabled="processing">Record expense</Button>
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
                    <div v-for="row in project.incomes" :key="`i-${row.id}`" class="flex justify-between">
                        <span class="text-green-600 dark:text-green-400">+ {{ row.description ?? 'Income' }}</span>
                        <span>{{ formatCurrency(row.amount, row.currency) }}</span>
                    </div>
                    <div v-for="row in project.expenses" :key="`e-${row.id}`" class="flex justify-between">
                        <span class="text-red-600 dark:text-red-400">− {{ row.description ?? 'Expense' }}</span>
                        <span>{{ formatCurrency(row.amount, row.currency) }}</span>
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
        <Card v-else-if="activeTab === 'issues'">
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Issues</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!project.issues.length" class="py-6 text-center text-sm text-muted-foreground">
                    No open issues.
                </div>
                <div v-else class="divide-y">
                    <div v-for="issue in project.issues" :key="issue.id" class="flex justify-between py-2">
                        <div>
                            <p class="font-medium">{{ issue.title }}</p>
                            <Badge variant="outline" class="mt-1">{{ issue.status }}</Badge>
                        </div>
                        <Badge :variant="issue.severity === 'high' ? 'destructive' : 'secondary'">
                            {{ issue.severity }}
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

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
    </MisPage>
</template>
