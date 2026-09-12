<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { nextTick, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

export type MisTabItem = {
    id: string;
    label: string;
    href?: string;
    icon?: Component;
};

withDefaults(
    defineProps<{
        tabs: MisTabItem[];
        class?: string;
        nowrap?: boolean;
    }>(),
    {
        class: undefined,
        nowrap: false,
    },
);

const model = defineModel<string>({ required: true });
const tablistEl = ref<HTMLElement | null>(null);

const isActive = (id: string) => String(model.value) === String(id);

const tabClass = (active: boolean) =>
    cn(
        'inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition -mb-px touch-manipulation',
        active
            ? 'border-primary bg-primary/8 text-primary'
            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
    );

async function scrollActiveIntoView() {
    await nextTick();
    const active = tablistEl.value?.querySelector<HTMLElement>(
        '[data-mis-tab][aria-selected="true"]',
    );
    active?.scrollIntoView({
        behavior: 'smooth',
        inline: 'center',
        block: 'nearest',
    });
}

watch(model, () => {
    void scrollActiveIntoView();
}, { immediate: true });
</script>

<template>
    <div
        :class="
            cn(
                'v2-page-tabs relative z-[2] flex items-end justify-between gap-2 border-b border-border/70 pb-0',
                $props.class,
            )
        "
    >
        <div
            class="relative min-w-0 flex-1"
            :class="
                nowrap
                    ? 'overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden'
                    : undefined
            "
        >
            <div
                v-if="nowrap"
                class="pointer-events-none absolute inset-y-0 start-0 z-[1] w-6 bg-gradient-to-r from-background to-transparent rtl:bg-gradient-to-l"
                aria-hidden="true"
            />
            <div
                v-if="nowrap"
                class="pointer-events-none absolute inset-y-0 end-0 z-[1] w-6 bg-gradient-to-l from-background to-transparent rtl:bg-gradient-to-r"
                aria-hidden="true"
            />
            <div
                ref="tablistEl"
                class="flex gap-1"
                :class="nowrap ? 'flex-nowrap' : 'flex-wrap'"
                role="tablist"
            >
                <Link
                    v-for="tab in tabs.filter((t) => t.href)"
                    :key="tab.id"
                    :href="tab.href!"
                    data-mis-tab
                    preserve-scroll
                    role="tab"
                    :aria-selected="isActive(tab.id)"
                    :class="tabClass(isActive(tab.id))"
                    @click="model = tab.id"
                >
                    <component
                        :is="tab.icon"
                        v-if="tab.icon"
                        class="size-4 shrink-0"
                    />
                    {{ tab.label }}
                </Link>
                <button
                    v-for="tab in tabs.filter((t) => !t.href)"
                    :key="`btn-${tab.id}`"
                    type="button"
                    data-mis-tab
                    role="tab"
                    :aria-selected="isActive(tab.id)"
                    :class="tabClass(isActive(tab.id))"
                    @click="model = tab.id"
                >
                    <component
                        :is="tab.icon"
                        v-if="tab.icon"
                        class="size-4 shrink-0"
                    />
                    {{ tab.label }}
                </button>
            </div>
        </div>
        <div v-if="$slots.actions" class="shrink-0 pb-2">
            <slot name="actions" />
        </div>
    </div>
</template>
