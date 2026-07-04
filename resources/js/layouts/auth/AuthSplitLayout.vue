<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-brand p-10 text-white lg:flex"
        >
            <div class="absolute inset-0 bg-brand" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center gap-3 text-lg font-medium"
            >
                <AppLogoImage class="size-12 shrink-0" />
                {{ name }}
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1></div>
                <slot />
            </div>
        </div>
    </div>
</template>
