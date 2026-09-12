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
import { V2DetailHero, V2ListPage } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatCurrency, formatDate } from '@/lib/format';

interface Organization {
    id: number;
    name: string;
}

interface Bid {
    id: number;
    bid_number: string | null;
    status: string;
    our_total_amount: number | null;
    currency: string | null;
}

interface Opportunity {
    id: number;
    reference_number: string | null;
    title: string;
    description: string | null;
    source: string | null;
    published_at: string | null;
    submission_deadline: string | null;
    estimated_value: number | null;
    currency: string | null;
    security_scope: string | null;
    location: string | null;
    duration_months: number | null;
    status: string;
    organization: Organization | null;
    bids: Bid[];
}

defineProps<{
    opportunity: Opportunity;
}>();

const { t, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Opportunities', href: '/bidding/opportunities' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="opportunity.title" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Opportunities') }}</template>
            <template #title>{{ opportunity.title }}</template>
            <template #description>
                {{ opportunity.reference_number ?? '—' }}
            </template>
            <template #actions>
                <Badge>{{ opportunity.status }}</Badge>
                <Badge v-if="opportunity.organization" variant="secondary">
                    {{ opportunity.organization.name }}
                </Badge>
                <Button as-child variant="outline">
                    <Link href="/bidding/opportunities">{{
                        t('Back to list')
                    }}</Link>
                </Button>
                <Button v-if="can('bidding.edit')" as-child>
                    <Link :href="`/bidding/opportunities/${opportunity.id}/edit`">{{
                        t('Edit')
                    }}</Link>
                </Button>
            </template>
        </V2DetailHero>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Details') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Published') }}</span>
                        <span>{{ formatDate(opportunity.published_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Submission deadline') }}</span>
                        <span>{{ formatDate(opportunity.submission_deadline) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Estimated value') }}</span>
                        <span>{{
                            formatCurrency(opportunity.estimated_value, opportunity.currency)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Location') }}</span>
                        <span>{{ opportunity.location ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Duration') }}</span>
                        <span>
                            {{
                                opportunity.duration_months
                                    ? t(':count months', {
                                          count: String(opportunity.duration_months),
                                      })
                                    : '—'
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ t('Source') }}</span>
                        <span>{{ opportunity.source ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Scope') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        {{ opportunity.security_scope ?? t('No scope defined.') }}
                    </p>
                    <p v-if="opportunity.description" class="mt-4 text-sm">
                        {{ opportunity.description }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('Bids') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="opportunity.bids.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No bids yet.') }}
                </div>
                <div v-else class="divide-y">
                    <Link
                        v-for="bid in opportunity.bids"
                        :key="bid.id"
                        :href="`/bidding/bids/${bid.id}`"
                        class="flex items-center justify-between py-3 transition-colors hover:bg-muted/50"
                    >
                        <div>
                            <p class="font-medium">
                                {{
                                    bid.bid_number ??
                                    t('Bid #') + bid.id
                                }}
                            </p>
                            <Badge variant="outline" class="mt-1">{{ bid.status }}</Badge>
                        </div>
                        <span class="text-sm font-medium">
                            {{ formatCurrency(bid.our_total_amount, bid.currency) }}
                        </span>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </V2ListPage>
</template>
