<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed, useAttrs } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const attrs = useAttrs()
const isFileInput = computed(() => attrs.type === "file")

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const inputClass = cn(
  'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-card/50 border-input h-9 w-full min-w-0 rounded-[0.875rem] border bg-white px-3 py-1 text-[0.9375rem] shadow-sm transition-[color,box-shadow,border-color] duration-150 outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
  'focus-visible:border-school-accent focus-visible:ring-[3px] focus-visible:ring-school-accent/30',
  'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
  props.class,
)
</script>

<template>
  <input
    v-if="isFileInput"
    v-bind="$attrs"
    data-slot="input"
    :class="inputClass"
  >
  <input
    v-else
    v-bind="$attrs"
    v-model="modelValue"
    data-slot="input"
    :class="inputClass"
  >
</template>
