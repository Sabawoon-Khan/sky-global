<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Organization {
    id: number;
    name: string;
}

interface ProjectActivity {
    id: number;
    title: string;
    activity_date: string | null;
    status: string | null;
}

interface ProjectIssue {
    id: number;
    title: string;
    severity: string | null;
    status: string;
    reported_at: string | null;
}

interface ProjectSite {
    id: number;
    name: string;
    location: string | null;
    status: string | null;
}

interface ProjectDocument {
    id: number;
    title: string;
    category: string | null;
    uploaded_at: string | null;
}

interface ProjectDetail {
    client_contact: string | null;
    risk_level: string | null;
    special_requirements: string | null;
}

interface FinanceSummary {
    income: number;
    expense: number;
    margin: number;
    currency: string;
}

interface Project {
    id: number;
    code: string;
    name: string;
    contract_number: string | null;
    contract_start: string | null;
    contract_end: string | null;
    scope_summary: string | null;
    total_contract_value: number | null;
    currency: string;
    status: string;
    organization: Organization | null;
    detail: ProjectDetail | null;
    activities: ProjectActivity[];
    issues: ProjectIssue[];
    sites: ProjectSite[];
    documents: ProjectDocument[];
    finance: FinanceSummary | null;
}

const props = defineProps<{
    project: Project;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
            { title: 'Details', href: '#' },
        ],
    },
});

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity' },
    { id: 'issues', label: 'Issues' },
    { id: 'finance', label: 'Finance' },
    { id: 'sites', label: 'Sites' },
    { id: 'documents', label: 'Documents' },
    { id: 'details', label: 'Details' },
] as const;

type TabId = (typeof tabs)[number]['id'];

const activeTab = ref<TabId>('overview');

const formatCurrency = (value: number | null, currency: string): string => {
    if (value === null) return '—';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
};

const finance = computed(() => props.project.finance);
</script>

<template>
    <Head :title="project.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading :title="project.name" :description="project.code" />
                <div class="mt-2 flex flex-wrap gap-2">
                    <Badge>{{ project.status }}</Badge>
                    <Badge v-if="project.organization" variant="secondary">
                        {{ project.organization.name }}
                    </Badge>
                </div>
            </div>
            <Button as-child variant="outline">
                <Link :href="`/projects/${project.id}/edit`">Edit</Link>
            </Button>
        </div>

        <div class="flex flex-wrap gap-2 border-b pb-2">
            <Button
                v-for="tab in tabs"
                :key="tab.id"
                size="sm"
                :variant="activeTab === tab.id ? 'default' : 'ghost'"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </Button>
        </div>

        <div v-if="activeTab === 'overview'" class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Contract</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Contract #</span>
                        <span>{{ project.contract_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Period</span>
                        <span
                            >{{ project.contract_start ?? '—' }} –
                            {{ project.contract_end ?? '—' }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Value</span>
                        <span class="font-medium">{{
                            formatCurrency(project.total_contract_value, project.currency)
                        }}</span>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Scope</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        {{ project.scope_summary ?? 'No scope summary.' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card v-else-if="activeTab === 'activity'">
            <CardHeader>
                <CardTitle>Activity Log</CardTitle>
                <CardDescription>Project milestones and activities</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="project.activities.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No activities recorded.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="activity in project.activities"
                        :key="activity.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="font-medium">{{ activity.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ activity.activity_date ?? '—' }}
                            </p>
                        </div>
                        <Badge v-if="activity.status" variant="outline">{{
                            activity.status
                        }}</Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'issues'">
            <CardHeader>
                <CardTitle>Issues</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="project.issues.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No open issues.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="issue in project.issues"
                        :key="issue.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="font-medium">{{ issue.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                Reported {{ issue.reported_at ?? '—' }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Badge v-if="issue.severity" variant="destructive">{{
                                issue.severity
                            }}</Badge>
                            <Badge variant="outline">{{ issue.status }}</Badge>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'finance'">
            <CardHeader>
                <CardTitle>Finance</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="finance" class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border p-4">
                        <p class="text-sm text-muted-foreground">Income</p>
                        <p class="text-xl font-bold">
                            {{ formatCurrency(finance.income, finance.currency) }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-4">
                        <p class="text-sm text-muted-foreground">Expense</p>
                        <p class="text-xl font-bold">
                            {{ formatCurrency(finance.expense, finance.currency) }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-4">
                        <p class="text-sm text-muted-foreground">Margin</p>
                        <p class="text-xl font-bold">
                            {{ formatCurrency(finance.margin, finance.currency) }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">No finance data available.</p>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'sites'">
            <CardHeader>
                <CardTitle>Sites</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="project.sites.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No sites configured.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="site in project.sites"
                        :key="site.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="font-medium">{{ site.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ site.location ?? '—' }}
                            </p>
                        </div>
                        <Badge v-if="site.status" variant="outline">{{ site.status }}</Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'documents'">
            <CardHeader>
                <CardTitle>Documents</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="project.documents.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No documents uploaded.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="doc in project.documents"
                        :key="doc.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="font-medium">{{ doc.title }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ doc.category ?? 'Uncategorized' }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            doc.uploaded_at ?? '—'
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-else-if="activeTab === 'details'">
            <CardHeader>
                <CardTitle>Extended Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <template v-if="project.detail">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Client contact</span>
                        <span>{{ project.detail.client_contact ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Risk level</span>
                        <span>{{ project.detail.risk_level ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Special requirements</span>
                        <p class="mt-1">
                            {{ project.detail.special_requirements ?? '—' }}
                        </p>
                    </div>
                </template>
                <p v-else class="text-muted-foreground">No extended details recorded.</p>
            </CardContent>
        </Card>
    </div>
</template>
