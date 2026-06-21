<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
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

interface Agreement {
    id: number;
    agreement_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    notes?: string | null;
}

interface Rate {
    id: number;
    daily_rate?: number | null;
    monthly_rate?: number | null;
    currency?: string | null;
    project?: { id: number; code: string; name: string } | null;
    effective_from?: string | null;
    effective_to?: string | null;
}

interface Contractor {
    id: number;
    first_name: string;
    last_name: string;
    father_name?: string | null;
    phone?: string | null;
    email?: string | null;
    tazkira_number?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    current_address?: string | null;
    status: string;
    agreements?: Agreement[];
    rates?: Rate[];
}

interface Props {
    contractor: Contractor;
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Contractors', href: '/hr/contractors' },
            { title: 'Profile', href: '#' },
        ],
    },
});

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
};

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
</script>

<template>
    <Head :title="`${contractor.first_name} ${contractor.last_name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start gap-4">
            <Button variant="outline" size="icon" as-child>
                <Link href="/hr/contractors">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div class="flex-1">
                <Heading
                    :title="`${contractor.first_name} ${contractor.last_name}`"
                    description="Contractor profile and agreements"
                />
            </div>
            <Badge>{{ contractor.status }}</Badge>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Personal Information</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Father's Name</span>
                        <span>{{ contractor.father_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Tazkira</span>
                        <span>{{ contractor.tazkira_number ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Phone</span>
                        <span>{{ contractor.phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Email</span>
                        <span>{{ contractor.email ?? '—' }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Agreements</CardTitle>
                    <CardDescription>Contractor service agreements</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!contractor.agreements?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No agreements on file.
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="agreement in contractor.agreements"
                            :key="agreement.id"
                            class="rounded-md border px-3 py-2 text-sm"
                        >
                            <div class="font-medium">
                                {{ agreement.agreement_number ?? 'Agreement' }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ formatDate(agreement.start_date) }} —
                                {{ formatDate(agreement.end_date) }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Rates</CardTitle>
                    <CardDescription>Project-specific billing rates</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!contractor.rates?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No rates configured.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">Project</th>
                                    <th class="pb-2 pr-4 font-medium">Daily</th>
                                    <th class="pb-2 pr-4 font-medium">Monthly</th>
                                    <th class="pb-2 font-medium">Effective</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="rate in contractor.rates"
                                    :key="rate.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-4">
                                        {{ rate.project?.code ?? 'General' }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{
                                            formatCurrency(
                                                rate.daily_rate,
                                                rate.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{
                                            formatCurrency(
                                                rate.monthly_rate,
                                                rate.currency ?? 'USD',
                                            )
                                        }}
                                    </td>
                                    <td class="py-2 text-muted-foreground">
                                        {{ formatDate(rate.effective_from) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
