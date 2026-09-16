<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FileLink from '@/components/FileLink.vue';
import TrainingFieldSubnav from '@/components/mis/TrainingFieldSubnav.vue';
import { Button } from '@/components/ui/button';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatDate } from '@/lib/format';

interface Guard {
    id: number;
    name: string;
    father_name: string;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    site?: string | null;
}

const props = defineProps<{
    report: {
        id: number;
        reference_number: string;
        report_date?: string | null;
        description: string;
        trainer_name?: string | null;
        attachment_url?: string | null;
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
            { title: 'On-site training', href: '/training/field/reports' },
            { title: 'Report', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="report.reference_number" />

    <V2ListPage>
        <TrainingFieldSubnav />

        <V2DetailHero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('On-site training') }}</template>
            <template #title>{{ report.reference_number }}</template>
            <template #description>
                {{ formatDate(report.report_date) }}
                <span v-if="report.trainer_name">
                    · {{ report.trainer_name }}
                </span>
                · {{ report.guards?.length ?? 0 }} {{ t('Guards') }}
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/training/field/reports">{{
                        t('Back to list')
                    }}</Link>
                </Button>
            </template>
        </V2DetailHero>

        <div class="grid gap-6 xl:grid-cols-3">
            <V2Panel :title="t('Report details')" class="xl:col-span-1">
                <div class="grid gap-3 text-sm">
                    <div>
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            {{ t('Report date') }}
                        </p>
                        <p class="mt-1 font-medium">
                            {{ formatDate(report.report_date) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            {{ t('Trainer') }}
                        </p>
                        <p class="mt-1 font-medium">
                            {{ report.trainer_name ?? '—' }}
                        </p>
                    </div>
                    <div v-if="report.created_by">
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            {{ t('Recorded by') }}
                        </p>
                        <p class="mt-1 font-medium">
                            {{ report.created_by.name }}
                        </p>
                    </div>
                    <div v-if="report.attachment_url">
                        <p class="text-xs font-medium uppercase text-muted-foreground">
                            {{ t('Attachment') }}
                        </p>
                        <FileLink
                            class="mt-1"
                            :href="report.attachment_url"
                            :label="
                                report.original_filename ?? t('Attachment')
                            "
                            show-icon
                        />
                    </div>
                </div>
            </V2Panel>

            <V2Panel :title="t('Description')" class="xl:col-span-2">
                <p class="whitespace-pre-wrap text-sm leading-relaxed">
                    {{ report.description }}
                </p>
            </V2Panel>
        </div>

        <V2Panel :title="t('Guards trained')" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">{{ t('Name') }}</th>
                            <th class="py-2">{{ t("Father's name") }}</th>
                            <th class="py-2">{{ t('Tazkira number') }}</th>
                            <th class="py-2">{{ t('Site') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="guard in report.guards ?? []"
                            :key="guard.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-2 font-medium">{{ guard.name }}</td>
                            <td class="py-2 text-muted-foreground">
                                {{ guard.father_name }}
                            </td>
                            <td class="py-2 text-muted-foreground">
                                {{ guard.tazkira_number ?? '—' }}
                            </td>
                            <td class="py-2 text-muted-foreground">
                                {{ guard.site ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </V2Panel>
    </V2ListPage>
</template>
