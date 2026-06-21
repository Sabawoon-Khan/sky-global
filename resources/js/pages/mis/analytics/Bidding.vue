<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, TrendingUp } from '@lucide/vue';
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

interface Summary {
    total_bids?: number;
    won?: number;
    lost?: number;
    win_rate?: number;
    pending?: number;
}

interface Props {
    summary?: Summary;
    bids: BidAnalytic[];
}

defineProps<Props>();

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
</script>

<template>
    <Head title="Bidding Analytics" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Bidding Analytics"
                description="Win rates, bid outcomes, and competitor trends"
            />
            <Button variant="outline" as-child>
                <Link href="/analytics/finance">Finance Analytics</Link>
            </Button>
        </div>

        <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Total Bids</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ summary.total_bids ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm">Win Rate</CardTitle>
                    <TrendingUp class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ summary.win_rate ?? 0 }}%
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Won / Lost</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ summary.won ?? 0 }} / {{ summary.lost ?? 0 }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Pending</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-bold">
                    {{ summary.pending ?? 0 }}
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <BarChart3 class="size-5" />
                    Recent Bids
                </CardTitle>
                <CardDescription>Latest bid submissions and outcomes</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="bids.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No bid data available.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Bid</th>
                                <th class="pb-3 pr-4 font-medium">Organization</th>
                                <th class="pb-3 pr-4 font-medium">Submitted</th>
                                <th class="pb-3 pr-4 font-medium">Our Amount</th>
                                <th class="pb-3 pr-4 font-medium">Winning</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bid in bids"
                                :key="bid.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <Link
                                        :href="`/bidding/bids/${bid.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ bid.bid_number ?? `#${bid.id}` }}
                                    </Link>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ bid.organization ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ formatDate(bid.submitted_at) }}
                                </td>
                                <td class="py-3 pr-4">
                                    {{
                                        formatCurrency(
                                            bid.our_total_amount,
                                            bid.currency ?? 'USD',
                                        )
                                    }}
                                </td>
                                <td class="py-3 pr-4">
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
                                        {{ bid.status }}
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
