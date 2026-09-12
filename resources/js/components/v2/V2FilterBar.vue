<template>
    <div class="filter-panel">
        <div class="toolbar">
            <slot />
            <div v-if="$slots.actions" class="ms-auto flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
            <div v-if="$slots.columns" class="toolbar-cols">
                <slot name="columns" />
            </div>
        </div>
    </div>
</template>
