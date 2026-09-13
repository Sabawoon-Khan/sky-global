<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import BlockPersonnelDialog from '@/components/BlockPersonnelDialog.vue';
import { useMisPage } from '@/composables/useMisPage';
import type { RowActionItem } from '@/lib/row-actions';
import { personnelStatusActions } from '@/lib/status-actions';

const props = defineProps<{
    url: string;
    name: string;
    status: string;
    blockable?: boolean;
}>();

const { t, can } = useMisPage();

const actions = computed(() =>
    can('hr.edit')
        ? personnelStatusActions({
              url: props.url,
              name: props.name,
              status: props.status,
              t,
              blockable: props.blockable,
          })
        : [],
);

const pendingAction = ref<RowActionItem | null>(null);
const processing = ref(false);

function handleClick(action: RowActionItem): void {
    if (action.form === 'block' || action.confirm) {
        pendingAction.value = action;

        return;
    }

    runAction(action);
}

function runAction(action: RowActionItem): void {
    if (!action.href || !action.method) {
        return;
    }

    processing.value = true;

    router.put(action.href, action.data ?? {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            pendingAction.value = null;
        },
    });
}

function confirmPendingAction(): void {
    if (pendingAction.value) {
        runAction(pendingAction.value);
    }
}
</script>

<template>
    <template v-if="actions.length">
        <Button
            v-for="(action, index) in actions"
            :key="`${action.label}-${index}`"
            :variant="action.variant === 'destructive' ? 'destructive' : 'outline'"
            :disabled="processing"
            @click="handleClick(action)"
        >
            <component :is="action.icon" v-if="action.icon" class="size-4" />
            {{ action.label }}
        </Button>
    </template>

    <BlockPersonnelDialog
        :open="pendingAction?.form === 'block'"
        :url="pendingAction?.href ?? url"
        :title="pendingAction?.confirm?.title ?? t('Block employee')"
        :hint="pendingAction?.confirm?.description ?? ''"
        @close="pendingAction = null"
    />

    <Dialog
        :open="pendingAction !== null && pendingAction.form !== 'block'"
        @update:open="(open) => !open && (pendingAction = null)"
    >
        <DialogContent v-if="pendingAction?.confirm">
            <DialogHeader>
                <DialogTitle>{{ pendingAction.confirm.title }}</DialogTitle>
                <DialogDescription>
                    {{ pendingAction.confirm.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" :disabled="processing">
                        {{ t('Cancel') }}
                    </Button>
                </DialogClose>
                <Button
                    :variant="pendingAction.confirmVariant ?? 'destructive'"
                    :disabled="processing"
                    @click="confirmPendingAction"
                >
                    {{
                        processing
                            ? t('Processing...')
                            : (pendingAction.confirm.confirmLabel ?? t('Confirm'))
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
