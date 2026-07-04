<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import ThemeColorSettings from '@/components/ThemeColorSettings.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Appearance settings')" />

    <h1 class="sr-only">{{ t('Appearance settings') }}</h1>

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{ t('Color mode') }}</CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Select light, dark, or system appearance for the interface.',
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AppearanceTabs />
            </CardContent>
        </Card>

        <ThemeColorSettings />
    </div>
</template>
