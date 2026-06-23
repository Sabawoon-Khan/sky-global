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
import { useMisNavigation } from '@/composables/useMisNavigation';
import { useTranslations } from '@/composables/useTranslations';
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
    stats: DashboardStats | null;
    projectProfitability: ProjectProfitability[];
    expiringDocuments: ExpiringDocument[];
}>();

const { t } = useTranslations();
const { misQuickLinks } = useMisNavigation();

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
    <Head :title="t('Dashboard')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="t('Dashboard')"
            :description="t('Overview of bidding, projects, finance, and HR')"
        />

        <div
            v-if="misQuickLinks.length > 0"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Link
                v-for="link in misQuickLinks"
                :key="link.href"
                :href="link.href"
                class="group rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted group-hover:bg-background"
                    >
                        <component :is="link.icon" class="size-5 text-muted-foreground" />
                    </div>
                    <div>
                        <p class="font-medium">{{ link.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ link.description }}
                        </p>
                    </div>
                </div>
            </Link>
        </div>

        <div
            v-if="stats"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Open Opportunities')
                    }}</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.bidding.open_opportunities }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.pending_bids }}
                        {{ t('pending bids') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Win Rate')
                    }}</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.bidding.win_rate }}%</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.bidding.won }} {{ t('won') }} /
                        {{ stats.bidding.lost }} {{ t('lost') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Active Projects')
                    }}</CardTitle>
                    <Briefcase class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.projects.active }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stats.projects.planning }} {{ t('planning') }} ·
                        {{ stats.projects.total }} {{ t('total') }}
                    </p>
                </CardContent>
            </Card>

            <Card class="transition-colors hover:bg-muted/30">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Net Finance (USD)')
                    }}</CardTitle>
                    <DollarSign class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <Link href="/finance" class="block space-y-1">
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
                            {{ t('Income') }}
                            {{ formatCurrency(stats.finance.total_income_usd) }} ·
                            {{ t('View finance') }}
                        </p>
                    </Link>
                </CardContent>
            </Card>

            <Card class="transition-colors hover:bg-muted/30">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Workforce')
                    }}</CardTitle>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <Link href="/hr/employees" class="block space-y-1">
                        <div class="text-2xl font-bold">
                            {{ stats.hr.employees + stats.hr.contractors }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.hr.employees }} {{ t('employees') }} ·
                            {{ stats.hr.contractors }} {{ t('contractors') }} ·
                            {{ t('View HR') }}
                        </p>
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Expiring Documents')
                    }}</CardTitle>
                    <AlertTriangle class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.hr.expiring_documents }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ t('Within 30 days') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium">{{
                        t('Competitor Intel')
                    }}</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.competitor_intel }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ t('Recorded competitor bids') }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="stats"
            class="grid gap-4 lg:grid-cols-2"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Profitability') }}</CardTitle>
                    <CardDescription>{{
                        t('Income vs expense by project')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="projectProfitability.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No project data available.') }}
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
                    <CardTitle>{{ t('Expiring Documents') }}</CardTitle>
                    <CardDescription>{{
                        t('Personnel attachments expiring soon')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="expiringDocuments.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No documents expiring within 30 days.') }}
                    </div>
                    <div v-else class="divide-y">
                        <div
                            v-for="doc in expiringDocuments.slice(0, 8)"
                            :key="doc.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div>
                                <p class="font-medium">{{ doc.type ?? t('Document') }}</p>
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
