<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <img
        src="/logo.png"
        alt="Sun Sky Global Security"
        :class="cn('size-full object-contain', props.class)"
    />
</template>
