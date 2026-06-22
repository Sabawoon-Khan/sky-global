<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Plus, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MisPagination from '@/components/MisPagination.vue';
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
import { formatCurrency, formatDate, type Paginated } from '@/lib/format';

interface Organization {
    id: number;
    name: string;
}

interface Opportunity {
    id: number;
    reference_number: string | null;
    title: string;
    status: string;
    submission_deadline: string | null;
    estimated_value: number | null;
    currency: string | null;
    location: string | null;
    security_scope: string | null;
    organization: Organization | null;
}

interface Props {
    opportunities: Paginated<Opportunity>;
    stats: {
        total: number;
        open: number;
        closed: number;
    };
    filters?: {
        search?: string | null;
        status?: string | null;
    };
}

defineProps<Props>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Opportunities', href: '/bidding/opportunities' },
        ],
    },
});

const statusVariant = (status: string) => {
    if (status === 'open') return 'default';
    if (status === 'closed') return 'secondary';
    if (status === 'awarded') return 'outline';
    return 'outline';
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        open: t('Open'),
        closed: t('Closed'),
        awarded: t('Awarded'),
        cancelled: t('Cancelled'),
    };

    return labels[status] ?? status;
};
</script>

<template>
    <Head :title="t('Opportunities')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Opportunities')"
                :description="t('Procurement requests and tenders from clients')"
            />
            <Button as-child>
                <Link href="/bidding/opportunities/create">
                    <Plus class="me-2 size-4" />
                    {{ t('New opportunity') }}
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Total opportunities') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Open for bidding') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.open }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('Closed / awarded') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.closed }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    {{ t('All opportunities') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count opportunities', {
                            count: String(
                                opportunities.meta?.total ?? opportunities.data.length,
                            ),
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/bidding/opportunities" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            :placeholder="t('Search title or reference...')"
                            class="ps-9"
                        />
                    </div>
                    <select
                        name="status"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="open" :selected="filters?.status === 'open'">
                            {{ t('Open') }}
                        </option>
                        <option value="closed" :selected="filters?.status === 'closed'">
                            {{ t('Closed') }}
                        </option>
                        <option value="awarded" :selected="filters?.status === 'awarded'">
                            {{ t('Awarded') }}
                        </option>
                        <option value="cancelled" :selected="filters?.status === 'cancelled'">
                            {{ t('Cancelled') }}
                        </option>
                    </select>
                    <Button type="submit" variant="secondary">{{ t('Filter') }}</Button>
                </form>

                <div
                    v-if="opportunities.data.length === 0"
                    class="rounded-lg border border-dashed p-10 text-center"
                >
                    <FileText class="mx-auto mb-3 size-10 text-muted-foreground" />
                    <p class="font-medium">{{ t('No opportunities yet') }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            t('Record procurement requests before creating bids.')
                        }}
                    </p>
                    <Button as-child class="mt-4">
                        <Link href="/bidding/opportunities/create">{{
                            t('Add first opportunity')
                        }}</Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Reference')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Title')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Organization')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Scope')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Deadline')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Value')
                                }}</th>
                                <th class="px-4 py-3 text-start font-medium">{{
                                    t('Status')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="opportunity in opportunities.data"
                                :key="opportunity.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-3 font-mono text-xs">
                                    {{ opportunity.reference_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <Link
                                        :href="`/bidding/opportunities/${opportunity.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ opportunity.title }}
                                    </Link>
                                    <p v-if="opportunity.location" class="text-xs text-muted-foreground">
                                        {{ opportunity.location }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ opportunity.organization?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ opportunity.security_scope ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatDate(opportunity.submission_deadline) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        formatCurrency(
                                            opportunity.estimated_value,
                                            opportunity.currency,
                                        )
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="statusVariant(opportunity.status)">
                                        {{ statusLabel(opportunity.status) }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <MisPagination :pagination="opportunities" />
            </CardContent>
        </Card>
    </div>
</template>
