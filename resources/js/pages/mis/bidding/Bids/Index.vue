<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Plus, Search } from '@lucide/vue';
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
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';

interface OpportunitySummary {
    id: number;
    title: string;
    organization?: { name: string } | null;
}

interface Bid {
    id: number;
    bid_number?: string | null;
    status: string;
    submitted_at?: string | null;
    our_total_amount?: number | null;
    winning_amount?: number | null;
    currency?: string | null;
    procurement_opportunity?: OpportunitySummary | null;
}

interface PaginatedBids {
    data: Bid[];
    meta?: { total: number };
}

interface Props {
    bids: PaginatedBids;
    filters?: { search?: string; status?: string };
}

defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bidding', href: '/bidding/opportunities' },
            { title: 'Bids', href: '/bidding/bids' },
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

const statusLabel = (status: string) => {
    if (status === 'won') return t('won');
    if (status === 'lost') return t('lost');
    if (status === 'pending') return t('pending');
    return status;
};
</script>

<template>
    <Head :title="t('Bids')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Bids')"
                :description="t('Manage submitted bids and outcomes')"
            />
            <MisCreateButton href="/bidding/bids/create" permission="bidding.create">
                <Plus class="me-1 size-4" />
                {{ t('New Bid') }}
            </MisCreateButton>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    {{ t('All Bids') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count bids', {
                            count: String(bids.meta?.total ?? bids.data.length),
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/bidding/bids" class="relative max-w-sm">
                    <Search
                        class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        :placeholder="t('Search bids...')"
                        class="ps-9"
                    />
                </form>

                <div
                    v-if="bids.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No bids found.') }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{ t('Bid #') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Opportunity')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Submitted')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Our Amount')
                                }}</th>
                                <th class="pb-3 font-medium">{{ t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bid in bids.data"
                                :key="bid.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        :href="`/bidding/bids/${bid.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ bid.bid_number ?? `#${bid.id}` }}
                                    </Link>
                                </td>
                                <td class="py-3 pe-4">
                                    <div class="font-medium">
                                        {{
                                            bid.procurement_opportunity?.title ??
                                            '—'
                                        }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            bid.procurement_opportunity?.organization
                                                ?.name ?? ''
                                        }}
                                    </div>
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
                                <td class="py-3">
                                    <Badge :variant="statusVariant(bid.status)">
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
