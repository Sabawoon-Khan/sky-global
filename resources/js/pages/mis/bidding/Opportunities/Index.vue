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
</script>

<template>
    <Head title="Bidding Opportunities" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Opportunities"
                description="Procurement requests and tenders from clients"
            />
            <Button as-child>
                <Link href="/bidding/opportunities/create">
                    <Plus class="mr-2 size-4" />
                    New Opportunity
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Total opportunities</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Open for bidding</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.open }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Closed / awarded</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.closed }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    All Opportunities
                </CardTitle>
                <CardDescription>
                    {{ opportunities.meta?.total ?? opportunities.data.length }} records
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/bidding/opportunities" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            placeholder="Search title or reference..."
                            class="pl-9"
                        />
                    </div>
                    <select
                        name="status"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="">All statuses</option>
                        <option value="open" :selected="filters?.status === 'open'">Open</option>
                        <option value="closed" :selected="filters?.status === 'closed'">Closed</option>
                        <option value="awarded" :selected="filters?.status === 'awarded'">Awarded</option>
                        <option value="cancelled" :selected="filters?.status === 'cancelled'">Cancelled</option>
                    </select>
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>

                <div
                    v-if="opportunities.data.length === 0"
                    class="rounded-lg border border-dashed p-10 text-center"
                >
                    <FileText class="mx-auto mb-3 size-10 text-muted-foreground" />
                    <p class="font-medium">No opportunities yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Record procurement requests before creating bids.
                    </p>
                    <Button as-child class="mt-4">
                        <Link href="/bidding/opportunities/create">Add first opportunity</Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Reference</th>
                                <th class="px-4 py-3 text-left font-medium">Title</th>
                                <th class="px-4 py-3 text-left font-medium">Organization</th>
                                <th class="px-4 py-3 text-left font-medium">Scope</th>
                                <th class="px-4 py-3 text-left font-medium">Deadline</th>
                                <th class="px-4 py-3 text-left font-medium">Value</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
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
                                        {{ opportunity.status }}
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
