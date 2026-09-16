<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FileLink from '@/components/FileLink.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate } from '@/lib/format';

interface Guard {
    id: number;
    name: string;
    father_name: string;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    batch_number: string;
    fee_number?: string | null;
    fee_amount?: number | string | null;
    status: string;
}

const props = defineProps<{
    payment: {
        id: number;
        reference_number: string;
        batch_number?: string | null;
        payment_date?: string | null;
        period_start?: string | null;
        period_end?: string | null;
        total_amount: number | string;
        currency?: string | null;
        notes?: string | null;
        receipt_url?: string | null;
        original_filename?: string | null;
        created_by?: { id: number; name: string } | null;
        guards?: Guard[];
    };
}>();

const { t } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Public Protection Deputy', href: '/training/payments' },
            { title: 'Payment', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="payment.reference_number" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Public Protection Deputy') }}</template>
            <template #title>{{ payment.reference_number }}</template>
            <template #description>
                <span v-if="payment.batch_number">
                    {{ t('Batch number') }} {{ payment.batch_number }}
                    ·
                </span>
                {{ formatAfn(Number(payment.total_amount)) }}
                ·
                {{ payment.guards?.length ?? 0 }}
                {{ t('Guards') }}
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/training/payments">{{ t('Back to list') }}</Link>
                </Button>
            </template>
        </V2DetailHero>

        <div class="grid gap-6 xl:grid-cols-3">
            <V2Panel :title="t('Payment details')" class="xl:col-span-1">
                <div class="grid gap-3 text-sm">
                    <div
                        v-if="payment.batch_number"
                        class="flex items-center justify-between rounded-md border px-3 py-2.5"
                    >
                        <span class="text-muted-foreground">{{ t('Batch number') }}</span>
                        <span class="font-medium">{{ payment.batch_number }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Payment date') }}</span>
                        <span class="font-medium">{{ formatDate(payment.payment_date) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Period') }}</span>
                        <span class="font-medium">
                            {{ formatDate(payment.period_start) }}
                            –
                            {{ formatDate(payment.period_end) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border px-3 py-2.5">
                        <span class="text-muted-foreground">{{ t('Total payment') }}</span>
                        <span class="font-medium">{{ formatAfn(Number(payment.total_amount)) }}</span>
                    </div>
                    <div
                        v-if="payment.created_by"
                        class="flex items-center justify-between rounded-md border px-3 py-2.5"
                    >
                        <span class="text-muted-foreground">{{ t('Recorded by') }}</span>
                        <span class="font-medium">{{ payment.created_by.name }}</span>
                    </div>
                    <div v-if="payment.notes" class="rounded-md border px-3 py-2.5">
                        <p class="text-xs text-muted-foreground">{{ t('Notes') }}</p>
                        <p class="mt-1 whitespace-pre-wrap">{{ payment.notes }}</p>
                    </div>
                    <FileLink
                        v-if="payment.receipt_url"
                        :href="payment.receipt_url"
                        :label="payment.original_filename ?? t('Receipt')"
                        show-icon
                    />
                </div>
            </V2Panel>

            <V2Panel :title="t('Guards in this payment')" class="xl:col-span-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2">{{ t('Name') }}</th>
                                <th class="py-2">{{ t("Father's name") }}</th>
                                <th class="py-2">{{ t('Batch number') }}</th>
                                <th class="py-2">{{ t('Fee amount') }}</th>
                                <th class="py-2">{{ t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="guard in payment.guards ?? []"
                                :key="guard.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2">
                                    <Link
                                        :href="`/training/guards/${guard.id}`"
                                        class="font-medium text-primary"
                                    >
                                        {{ guard.name }}
                                    </Link>
                                </td>
                                <td class="py-2 text-muted-foreground">
                                    {{ guard.father_name }}
                                </td>
                                <td class="py-2">{{ guard.batch_number }}</td>
                                <td class="py-2">
                                    {{ formatAfn(Number(guard.fee_amount ?? 0)) }}
                                </td>
                                <td class="py-2">
                                    <StatusBadge :status="guard.status" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </V2Panel>
        </div>
    </V2ListPage>
</template>
