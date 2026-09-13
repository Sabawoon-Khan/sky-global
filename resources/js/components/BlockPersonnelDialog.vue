<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    open: boolean;
    url: string;
    title: string;
    hint: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useTranslations();

function onOpenChange(open: boolean): void {
    if (!open) {
        emit('close');
    }
}
</script>

<template>
    <Dialog :open="props.open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-lg">
            <Form
                :action="props.url"
                method="put"
                #default="{ processing, errors }"
                :options="{
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => emit('close'),
                }"
            >
                <DialogHeader>
                    <DialogTitle>{{ props.title }}</DialogTitle>
                    <DialogDescription>{{ props.hint }}</DialogDescription>
                </DialogHeader>

                <input type="hidden" name="status" value="blocked" />

                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="block-reason">
                            {{ t('Description') }}
                            <span class="text-destructive">*</span>
                        </Label>
                        <Textarea
                            id="block-reason"
                            name="reason"
                            :rows="4"
                            required
                            :placeholder="t('Describe why this employee is being blocked.')"
                        />
                        <InputError :message="errors.reason" />
                    </div>

                    <OptionalAttachmentField
                        name="attachment"
                        label="Blocking document"
                        required
                        :error="errors.attachment"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        :disabled="processing"
                        @click="emit('close')"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" variant="destructive" :disabled="processing">
                        {{ processing ? t('Processing...') : t('Block') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
