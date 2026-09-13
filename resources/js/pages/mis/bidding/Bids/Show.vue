<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    V2DetailHero,
    V2ListPage,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import SortableTh from '@/components/SortableTh.vue';
import { formatCurrency } from '@/lib/format';

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

const props = defineProps<Props>();

const { t } = useMisPage();

const { sortedRows } = provideTableSort(() => props.bid.line_items ?? [], {
    accessors: {
        description: (row) => row.description,
        quantity: (row) => row.quantity,
        total: (row) => row.total,
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bidding', href: '/bidding/opportunities' },
            { title: 'Bids', href: '/bidding/bids' },
            { title: 'Details', href: '#' },
        ],
    },
});

const bidTitle = (bid: Bid): string => bid.bid_number ?? `${t('Bid #')}${bid.id}`;


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
    <Head :title="bidTitle(bid)" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Bidding') }}</template>
            <template #title>{{ bidTitle(bid) }}</template>
            <template #description>
                {{ bid.procurement_opportunity?.title ?? '—' }}
            </template>
            <template #actions>
                <Badge :variant="statusVariant(bid.status)">
                    {{ bid.status }}
                </Badge>
                <Button variant="outline" as-child>
                    <Link href="/bidding/bids">{{ t('Back to list') }}</Link>
                </Button>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Our Amount')"
                        :value="
                            formatCurrency(
                                bid.our_total_amount,
                                bid.currency ?? 'AFN',
                            )
                        "
                    />
                    <V2StatCard
                        :delay="1"
                        :title="t('Winning Amount')"
                        :value="
                            formatCurrency(
                                bid.winning_amount,
                                bid.currency ?? 'AFN',
                            )
                        "
                    />
                    <V2StatCard
                        :delay="2"
                        :title="t('Submitted')"
                        :value="formatDate(bid.submitted_at)"
                    />
                    <V2StatCard
                        :delay="3"
                        :title="t('Organization')"
                        :value="
                            bid.procurement_opportunity?.organization?.name ??
                            '—'
                        "
                    />
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Line Items') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!bid.line_items?.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No line items recorded.') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <SortableTh column="description" class="pb-2 pr-4 font-medium">
                                        {{ t('Description') }}
                                    </SortableTh>
                                    <SortableTh column="quantity" align="end" class="pb-2 pr-4 text-right font-medium">
                                        {{ t('Qty') }}
                                    </SortableTh>
                                    <SortableTh column="total" align="end" class="pb-2 text-right font-medium">
                                        {{ t('Total') }}
                                    </SortableTh>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in sortedRows"
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
                                                bid.currency ?? 'AFN',
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
                    <CardTitle>{{ t('Competitor Intelligence') }}</CardTitle>
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
                                    {{ t('Winning') }}
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
                                    competitor.currency ?? bid.currency ?? 'AFN',
                                )
                            }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="bid.status === 'lost'">
                <CardHeader>
                    <CardTitle>{{ t('Loss Details') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div v-if="bid.winning_competitor_name">
                        <span class="text-muted-foreground">{{ t('Winner:') }} </span>
                        {{ bid.winning_competitor_name }}
                    </div>
                    <div v-if="bid.loss_reason">
                        <span class="text-muted-foreground">{{ t('Reason:') }} </span>
                        {{ bid.loss_reason }}
                    </div>
                    <p v-if="bid.notes" class="text-muted-foreground">
                        {{ bid.notes }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </V2ListPage>
</template>
