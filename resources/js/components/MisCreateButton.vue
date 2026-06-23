<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

const props = withDefaults(
    defineProps<{
        href: NonNullable<InertiaLinkProps['href']>;
        permission: string;
        size?: 'default' | 'sm' | 'lg' | 'icon';
        class?: string;
    }>(),
    {
        size: 'default',
    },
);

const { can } = usePermissions();
</script>

<template>
    <Button
        v-if="can(permission)"
        as-child
        :size="size"
        :class="props.class"
    >
        <Link :href="href">
            <slot />
        </Link>
    </Button>
</template>
