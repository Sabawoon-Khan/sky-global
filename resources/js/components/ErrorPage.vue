<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, home } from '@/routes';

const { code, title, icon } = defineProps<{
    code: number;
    title: string;
    icon: Component;
}>();

const page = usePage();
const { t } = useTranslations();

const appName = computed(() => page.props.name as string);
const displayTitle = computed(() => t(title));

const primaryHref = computed(() =>
    page.props.auth.user ? dashboard() : home(),
);

const primaryLabel = computed(() =>
    page.props.auth.user ? t('Go to dashboard') : t('Go to home'),
);
</script>

<template>
    <Head :title="displayTitle" />

    <div class="relative min-h-screen overflow-hidden bg-background">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(32,48,112,0.14),transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(32,48,112,0.2),transparent)]"
        />

        <div
            class="relative mx-auto flex min-h-screen max-w-2xl flex-col px-4 sm:px-6"
        >
            <header class="flex items-center justify-between py-6">
                <Link
                    :href="home()"
                    class="flex items-center gap-3 transition-opacity hover:opacity-80"
                >
                    <AppLogoImage class="size-10" />
                    <span class="text-sm font-semibold tracking-tight">{{
                        appName
                    }}</span>
                </Link>

                <LanguageSwitcher />
            </header>

            <main
                class="flex flex-1 flex-col items-center justify-center pb-16 text-center"
            >
                <div
                    class="mb-6 flex size-16 items-center justify-center rounded-2xl bg-muted"
                >
                    <component :is="icon" class="size-8 text-muted-foreground" />
                </div>

                <p class="text-7xl font-bold tracking-tighter text-foreground/20">
                    {{ code }}
                </p>

                <h1 class="mt-4 text-2xl font-semibold tracking-tight">
                    {{ displayTitle }}
                </h1>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button as-child>
                        <Link :href="primaryHref">
                            {{ primaryLabel }}
                        </Link>
                    </Button>
                    <Button variant="outline" type="button" @click="history.back()">
                        {{ t('Go back') }}
                    </Button>
                </div>
            </main>
        </div>
    </div>
</template>
