<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Building2, Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useMisPage } from '@/composables/useMisPage';

const props = defineProps<{
    employeeId: number;
    isPermanent: boolean;
}>();

const { t, can } = useMisPage();
const processing = ref(false);

const canEdit = computed(() => can('hr.edit'));

function togglePermanent(checked: boolean | 'indeterminate'): void {
    if (!canEdit.value || checked === 'indeterminate' || processing.value) {
        return;
    }

    processing.value = true;

    router.put(
        `/hr/employees/${props.employeeId}`,
        { is_permanent: checked },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <div
        class="flex items-start gap-4 rounded-lg border p-4 transition-colors"
        :class="
            isPermanent
                ? 'border-primary/30 bg-primary/5'
                : 'border-border bg-muted/30'
        "
    >
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-full"
            :class="isPermanent ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground'"
        >
            <Building2 class="size-5" />
        </div>
        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <p class="font-medium">
                    {{ isPermanent ? t('Permanent staff') : t('Project-based staff') }}
                </p>
                <span
                    v-if="isPermanent"
                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                >
                    <Check class="size-3" />
                    {{ t('Always at office') }}
                </span>
            </div>
            <p class="text-sm text-muted-foreground">
                {{
                    isPermanent
                        ? t('Included in general attendance. Not assigned to projects.')
                        : t('Assigned to projects for attendance and payroll.')
                }}
            </p>
            <div v-if="canEdit" class="flex items-center gap-2 pt-2">
                <Checkbox
                    :id="`permanent-${employeeId}`"
                    :model-value="isPermanent"
                    :disabled="processing"
                    @update:model-value="togglePermanent"
                />
                <Label
                    :for="`permanent-${employeeId}`"
                    class="cursor-pointer text-sm font-normal"
                >
                    {{ t('Mark as permanent staff') }}
                </Label>
            </div>
        </div>
    </div>
</template>
