<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions';
import { computed } from 'vue';

const props = defineProps<{
    permission: string | string[];
}>();

const { can, canAny } = usePermissions();

const allowed = computed(() =>
    Array.isArray(props.permission)
        ? canAny(props.permission)
        : can(props.permission),
);
</script>

<template>
    <slot v-if="allowed" />
</template>
