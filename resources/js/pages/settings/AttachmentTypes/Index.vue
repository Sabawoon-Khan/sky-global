<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FileText, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';

interface AttachmentTypeRecord {
    id: number;
    name: string;
    slug: string;
    requires_expiry?: boolean;
    is_active?: boolean;
    sort_order?: number;
    personnel_attachments_count?: number;
}

defineProps<{
    attachmentTypes: AttachmentTypeRecord[];
}>();

const { t } = useTranslations();

const editingType = ref<AttachmentTypeRecord | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Form Types', href: '/settings/form-types' },
        ],
    },
});

const openEdit = (type: AttachmentTypeRecord): void => {
    editingType.value = type;
};

const closeEdit = (): void => {
    editingType.value = null;
};

const formTypeActions = (type: AttachmentTypeRecord): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        onClick: () => openEdit(type),
    },
    toggleIsActiveAction({
        url: `/settings/form-types/${type.id}`,
        name: type.name,
        isActive: type.is_active ?? true,
        entityLabel: 'form type',
        t,
    }),
    {
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/settings/form-types/${type.id}`,
        method: 'delete',
        disabled: (type.personnel_attachments_count ?? 0) > 0,
        confirm: {
            title: t('Delete form type'),
            description: t('Are you sure you want to delete ":name"? This cannot be undone.', {
                name: type.name,
            }),
            confirmLabel: t('Delete'),
        },
    },
];
</script>

<template>
    <Head :title="t('Form Types')" />

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    {{ t('Form Types') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Configure HR document types such as guarantee forms, certificates, and clearances') }}
                </CardDescription>
                <CardAction>
                    <SettingsAddButton href="/settings/form-types/create">
                        {{ t('Add form type') }}
                    </SettingsAddButton>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div
                    v-if="attachmentTypes.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No form types configured.') }}
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="type in attachmentTypes"
                        :key="type.id"
                        class="ui-list-row flex items-center justify-between px-4 py-3"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ type.name }}</span>
                                <Badge v-if="type.requires_expiry" variant="secondary">
                                    {{ t('Expiry required') }}
                                </Badge>
                                <Badge v-if="type.is_active === false" variant="outline">
                                    {{ t('Inactive') }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ type.slug }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-muted-foreground">
                                {{ type.personnel_attachments_count ?? 0 }}
                                {{ t('uploads') }}
                            </span>
                            <RowActionsMenu :actions="formTypeActions(type)" />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <Dialog
        :open="editingType !== null"
        @update:open="(open) => !open && closeEdit()"
    >
        <DialogContent v-if="editingType">
            <Form
                :action="`/settings/form-types/${editingType.id}`"
                method="put"
                @success="closeEdit"
                v-slot="{ processing, errors }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit form type') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Update settings for :name.', { name: editingType.name }) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            :default-value="editingType.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="edit-requires-expiry"
                            name="requires_expiry"
                            type="checkbox"
                            value="1"
                            class="size-4 rounded border-input"
                            :checked="editingType.requires_expiry"
                        />
                        <Label for="edit-requires-expiry" class="font-normal">
                            {{ t('Requires expiry date') }}
                        </Label>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-sort-order">{{ t('Sort order') }}</Label>
                        <Input
                            id="edit-sort-order"
                            name="sort_order"
                            type="number"
                            min="0"
                            :default-value="editingType.sort_order ?? 0"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="closeEdit">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('Save changes') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
