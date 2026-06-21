<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Briefcase, DollarSign, TrendingUp, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

interface DashboardStats {
    bidding: {
        open_opportunities: number;
        pending_bids: number;
        win_rate: number;
        won: number;
        lost: number;
    };
    projects: {
        active: number;
        planning: number;
        total: number;
    };
    finance: {
        total_income_usd: number;
        total_expense_usd: number;
        overhead_usd: number;
    };
    hr: {
        employees: number;
        contractors: number;
        expiring_documents: number;
    };
    organization_types: Array<{
        id: number;
        name: string;
        color: string | null;
        organizations_count: number;
        projects_count: number;
        total_contract_value: number;
    }>;
    competitor_intel: number;
}

interface ProjectProfitability {
    id: number;
    code: string;
    name: string;
    organization: string | null;
    income: number;
    expense: number;
    margin: number;
}

interface ExpiringDocument {
    id: number;
    personnel_type: string;
    personnel_id: number;
    type: string | null;
    expires_at: string | null;
}

defineProps<{
    stats: DashboardStats;
    projectProfitability: ProjectProfitability[];
    expiringDocuments: ExpiringDocument[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const formatCurrency = (value: number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            description="Overview of bidding, projects, finance, and HR"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Open Opportunities</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.bidding.open_opportunities }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.pending_bids }} pending bids
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Win Rate</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.bidding.win_rate }}%</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.won }} won / {{ stats.bidding.lost }} lost
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Active Projects</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.projects.active }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.projects.planning }} planning ·
                        {{ stats.projects.total }} total
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Net Finance (USD)</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{
                            formatCurrency(
                                stats.finance.total_income_usd -
                                    stats.finance.total_expense_usd -
                                    stats.finance.overhead_usd,
                            )
                        }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Income {{ formatCurrency(stats.finance.total_income_usd) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Workforce</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.hr.employees + stats.hr.contractors }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.hr.employees }} employees ·
                        {{ stats.hr.contractors }} contractors
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Expiring Documents</CardTitle>
                    <AlertTriangle class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.hr.expiring_documents }}</div>
                    <p class="text-xs text-muted-foreground">Within 30 days</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">Competitor Intel</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.competitor_intel }}</div>
                    <p class="text-xs text-muted-foreground">Recorded competitor bids</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Project Profitability</CardTitle>
                    <CardDescription>Income vs expense by project</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="projectProfitability.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No project data available.
                    </div>
                    <div v-else class="divide-y">
                        <Link
                            v-for="project in projectProfitability.slice(0, 8)"
                            :key="project.id"
                            :href="`/projects/${project.id}`"
                            class="flex items-center justify-between py-3 transition-colors hover:bg-muted/50"
                        >
                            <div>
                                <p class="font-medium">{{ project.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ project.code }}
                                    <span v-if="project.organization">
                                        · {{ project.organization }}
                                    </span>
                                </p>
                            </div>
                            <Badge
                                :variant="project.margin >= 0 ? 'default' : 'destructive'"
                            >
                                {{ formatCurrency(project.margin) }}
                            </Badge>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Expiring Documents</CardTitle>
                    <CardDescription>Personnel attachments expiring soon</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="expiringDocuments.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No documents expiring within 30 days.
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="doc in expiringDocuments.slice(0, 8)"
                            :key="doc.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="font-medium">{{ doc.type ?? 'Document' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ doc.personnel_type }} #{{ doc.personnel_id }}
                                </p>
                            </div>
                            <Badge variant="outline">{{ doc.expires_at }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
