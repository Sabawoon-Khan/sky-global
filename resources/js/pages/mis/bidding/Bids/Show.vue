<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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

interface Opportunity {
    id: number;
    title: string;
    organization?: Organization | null;
}

interface CompetitorBid {
    id: number;
    competitor_name: string;
    bid_amount?: number | null;
    currency?: string | null;
    is_winner?: boolean;
    notes?: string | null;
}

interface BidLineItem {
    id: number;
    description: string;
    quantity?: number | null;
    unit_price?: number | null;
    total?: number | null;
}

interface Bid {
    id: number;
    bid_number?: string | null;
    status: string;
    submitted_at?: string | null;
    our_total_amount?: number | null;
    winning_amount?: number | null;
    winning_competitor_name?: string | null;
    loss_reason?: string | null;
    currency?: string | null;
    notes?: string | null;
    procurement_opportunity?: Opportunity | null;
    line_items?: BidLineItem[];
}

interface Props {
    bid: Bid;
    competitors?: CompetitorBid[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bidding', href: '/bidding/opportunities' },
            { title: 'Bids', href: '/bidding/bids' },
            { title: 'Details', href: '#' },
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

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'won') {
        return 'default';
    }

    if (status === 'lost') {
        return 'destructive';
    }

    return 'secondary';
};
</script>

<template>
    <Head :title="bid.bid_number ?? `Bid #${bid.id}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Heading
                    :title="bid.bid_number ?? `Bid #${bid.id}`"
                    :description="bid.procurement_opportunity?.title"
                />
                <Badge class="mt-2" :variant="statusVariant(bid.status)">
                    {{ bid.status }}
                </Badge>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link href="/bidding/bids">Back to list</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Our Amount</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold">
                    {{
                        formatCurrency(bid.our_total_amount, bid.currency ?? 'USD')
                    }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Winning Amount</CardTitle>
                </CardHeader>
                <CardContent class="text-lg font-semibold">
                    {{
                        formatCurrency(bid.winning_amount, bid.currency ?? 'USD')
                    }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Submitted</CardTitle>
                </CardHeader>
                <CardContent class="text-sm">
                    {{ formatDate(bid.submitted_at) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm">Organization</CardTitle>
                </CardHeader>
                <CardContent class="text-sm">
                    {{
                        bid.procurement_opportunity?.organization?.name ?? '—'
                    }}
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Line Items</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!bid.line_items?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No line items recorded.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">
                                        Description
                                    </th>
                                    <th class="pb-2 pr-4 text-right font-medium">
                                        Qty
                                    </th>
                                    <th class="pb-2 text-right font-medium">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in bid.line_items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-4">{{ item.description }}</td>
                                    <td class="py-2 pr-4 text-right">
                                        {{ item.quantity ?? '—' }}
                                    </td>
                                    <td class="py-2 text-right">
                                        {{
                                            formatCurrency(
                                                item.total,
                                                bid.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="competitors?.length">
                <CardHeader>
                    <CardTitle>Competitor Intelligence</CardTitle>
                    <CardDescription>
                        Known competitor bids for this opportunity
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div
                        v-for="competitor in competitors"
                        :key="competitor.id"
                        class="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                    >
                        <div>
                            <div class="font-medium">
                                {{ competitor.competitor_name }}
                                <Badge
                                    v-if="competitor.is_winner"
                                    class="ml-2"
                                    variant="default"
                                >
                                    Winner
                                </Badge>
                            </div>
                            <p
                                v-if="competitor.notes"
                                class="text-xs text-muted-foreground"
                            >
                                {{ competitor.notes }}
                            </p>
                        </div>
                        <span class="font-medium">
                            {{
                                formatCurrency(
                                    competitor.bid_amount,
                                    competitor.currency ?? bid.currency ?? 'USD',
                                )
                            }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="bid.status === 'lost'">
                <CardHeader>
                    <CardTitle>Loss Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div v-if="bid.winning_competitor_name">
                        <span class="text-muted-foreground">Winner: </span>
                        {{ bid.winning_competitor_name }}
                    </div>
                    <div v-if="bid.loss_reason">
                        <span class="text-muted-foreground">Reason: </span>
                        {{ bid.loss_reason }}
                    </div>
                    <p v-if="bid.notes" class="text-muted-foreground">
                        {{ bid.notes }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
