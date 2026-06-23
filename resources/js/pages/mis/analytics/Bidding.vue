<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, TrendingUp } from '@lucide/vue';
import Can from '@/components/Can.vue';
import Heading from '@/components/Heading.vue';
import MisCreateButton from '@/components/MisCreateButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMisPage } from '@/composables/useMisPage';
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { translateBidStatus, translateProjectStatus } from '@/lib/status-labels';

interface BidAnalytic {
    id: number;
    bid_number?: string | null;
    status: string;
    our_total_amount?: number | null;
    winning_amount?: number | null;
    currency?: string | null;
    organization?: string | null;
    submitted_at?: string | null;
}

interface BiddingStats {
    open_opportunities?: number;
    pending_bids?: number;
    win_rate?: number;
    won?: number;
    lost?: number;
}

interface OrganizationTypeStat {
    id: number;
    name: string;
    color: string | null;
    organizations_count: number;
    projects_count: number;
    total_contract_value: number;
}

interface Props {
    stats?: BiddingStats;
    organizationTypes?: OrganizationTypeStat[];
    competitorIntel?: number;
    bids: BidAnalytic[];
    charts?: {
        bidding_outcomes: Array<{ key: string; value: number }>;
        project_statuses: Array<{ status: string; count: number }>;
        monthly_bids: Array<{ label: string; submitted: number; won: number; lost: number; win_rate: number }>;
        organization_types: Array<{ name: string; projects_count: number; total_contract_value: number }>;
        bid_statuses: Array<{ status: string; count: number }>;
    };
}

const props = defineProps<Props>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Analytics', href: '/analytics/bidding' },
            { title: 'Bidding', href: '/analytics/bidding' },
        ],
    },
});

const formatCurrency = (value?: number | null, currency = 'USD'): string => {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

const statusLabel = (status: string) => translateBidStatus(t, status);

const outcomeLabel = (key: string): string => {
    if (key === 'won') return t('won');
    if (key === 'lost') return t('lost');
    if (key === 'pending') return t('Pending');
    return key;
};
</script>

<template>
    <Head :title="t('Bidding Analytics')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Bidding Analytics')"
                :description="t('Win rates, bid outcomes, and competitor trends')"
            />
            <div class="flex flex-wrap gap-2">
                <Can permission="projects.view">
                    <Button variant="outline" as-child>
                        <Link href="/projects">{{ t('View Projects') }}</Link>
                    </Button>
                </Can>
                <MisCreateButton
                    href="/projects/create"
                    permission="projects.create"
                >
                    {{ t('New Project') }}
                </MisCreateButton>
                <Can permission="finance.view">
                    <Button variant="outline" as-child>
                        <Link href="/analytics/finance">{{ t('Finance Analytics') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div v-if="stats" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Open Opportunities') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ stats.open_opportunities ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm">{{ t('Win Rate') }}</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ stats.win_rate ?? 0 }}%
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Won / Lost') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ stats.won ?? 0 }} / {{ stats.lost ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">{{ t('Pending Bids') }}</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ stats.pending_bids ?? 0 }}
                </CardContent>
            </Card>
        </div>

        <div v-if="charts" class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Bidding Outcomes') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.bidding_outcomes.map((b) => outcomeLabel(b.key))"
                        :data="charts.bidding_outcomes.map((b) => b.value)"
                    />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Project Status Breakdown') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.project_statuses.map((s) => translateProjectStatus(t, s.status))"
                        :datasets="[{ label: t('Projects'), data: charts.project_statuses.map((s) => s.count) }]"
                    />
                </CardContent>
            </Card>
            <Card class="lg:col-span-2 xl:col-span-1">
                <CardHeader>
                    <CardTitle>{{ t('Monthly Bid Activity') }}</CardTitle>
                    <CardDescription>{{ t('Submissions, wins, and losses') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_bids.map((m) => m.label)"
                        :datasets="[
                            { label: t('Submitted'), data: charts.monthly_bids.map((m) => m.submitted) },
                            { label: t('won'), data: charts.monthly_bids.map((m) => m.won) },
                            { label: t('lost'), data: charts.monthly_bids.map((m) => m.lost) },
                        ]"
                    />
                </CardContent>
            </Card>
            <Card v-if="charts.bid_statuses?.length">
                <CardHeader>
                    <CardTitle>{{ t('Bid Status Breakdown') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.bid_statuses.map((s) => translateBidStatus(t, s.status))"
                        :data="charts.bid_statuses.map((s) => s.count)"
                    />
                </CardContent>
            </Card>
            <Card v-if="charts.organization_types.length" class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Contract Value by Org Type') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="charts.organization_types.map((orgType) => orgType.name)"
                        :datasets="[{ label: t('Contract Value'), data: charts.organization_types.map((orgType) => orgType.total_contract_value) }]"
                    />
                </CardContent>
            </Card>
            <Card v-if="charts.organization_types.length">
                <CardHeader>
                    <CardTitle>{{ t('Projects by Org Type') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :labels="charts.organization_types.map((orgType) => orgType.name)"
                        :data="charts.organization_types.map((orgType) => orgType.projects_count)"
                    />
                </CardContent>
            </Card>
            <Card v-if="stats" class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Win Rate Overview') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :labels="[t('won'), t('lost'), t('Pending')]"
                        :datasets="[
                            {
                                label: t('Bids'),
                                data: [stats?.won ?? 0, stats?.lost ?? 0, stats?.pending_bids ?? 0],
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('Win Rate Trend') }}</CardTitle>
                    <CardDescription>{{ t('Monthly win rate percentage') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :labels="charts.monthly_bids.map((m) => m.label)"
                        :datasets="[
                            {
                                label: t('Win Rate'),
                                data: charts.monthly_bids.map((m) => m.win_rate),
                                borderColor: 'rgba(34, 197, 94, 1)',
                            },
                        ]"
                    />
                </CardContent>
            </Card>
        </div>

        <div
            v-if="organizationTypes?.length || competitorIntel"
            class="grid gap-4 lg:grid-cols-2"
        >
            <Card v-if="organizationTypes?.length">
                <CardHeader>
                    <CardTitle>{{ t('By Organization Type') }}</CardTitle>
                    <CardDescription>{{
                        t('Projects and contract value by client type')
                    }}</CardDescription>
                </CardHeader>
                <CardContent class="divide-y">
                    <div
                        v-for="type in organizationTypes"
                        :key="type.id"
                        class="flex items-center justify-between py-3 text-sm"
                    >
                        <div>
                            <p class="font-medium">{{ type.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ type.organizations_count }} {{ t('organizations') }} ·
                                {{ type.projects_count }} {{ t('projects') }}
                            </p>
                        </div>
                        <span class="font-medium">
                            {{ formatCurrency(type.total_contract_value) }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="competitorIntel !== undefined">
                <CardHeader>
                    <CardTitle>{{ t('Competitor Intel') }}</CardTitle>
                    <CardDescription>{{
                        t('Recorded competitor bids across projects')
                    }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-3xl font-bold">{{ competitorIntel ?? 0 }}</p>
                    <Can permission="bidding.view_competitors">
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/projects">{{ t('Review on projects') }}</Link>
                        </Button>
                    </Can>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <BarChart3 class="size-5" />
                    {{ t('Recent Bids') }}
                </CardTitle>
                <CardDescription>{{
                    t('Latest bid submissions and outcomes')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="bids.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No bid data available.') }}
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{ t('Bid') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Organization')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Submitted')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Our Amount')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Winning')
                                }}</th>
                                <th class="pb-3 font-medium">{{ t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bid in bids"
                                :key="bid.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        v-if="can('projects.view')"
                                        :href="`/projects`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ bid.bid_number ?? `#${bid.id}` }}
                                    </Link>
                                    <span v-else class="font-medium">
                                        {{ bid.bid_number ?? `#${bid.id}` }}
                                    </span>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ bid.organization ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ formatDate(bid.submitted_at) }}
                                </td>
                                <td class="py-3 pe-4">
                                    {{
                                        formatCurrency(
                                            bid.our_total_amount,
                                            bid.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3 pe-4">
                                    {{
                                        formatCurrency(
                                            bid.winning_amount,
                                            bid.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3">
                                    <Badge
                                        :variant="
                                            bid.status === 'won'
                                                ? 'default'
                                                : bid.status === 'lost'
                                                  ? 'destructive'
                                                  : 'secondary'
                                        "
                                    >
                                        {{ statusLabel(bid.status) }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
