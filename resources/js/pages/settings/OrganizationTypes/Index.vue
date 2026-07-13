<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Pencil, Tags, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import InputError from '@/components/InputError.vue';
import { useAutoSlug } from '@/composables/useAutoSlug';
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

interface OrganizationType {
    id: number;
    name: string;
    slug: string;
    color?: string | null;
    description?: string | null;
    is_active?: boolean;
    organizations_count?: number;
}

interface Props {
    organizationTypes: OrganizationType[];
}

defineProps<Props>();

const { t } = useTranslations();
const page = usePage();

const pageErrors = computed(() => {
    const errors = page.props.errors as Record<string, string>;

    return Object.values(errors).filter(Boolean);
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            {
                title: 'Organization Types',
                href: '/settings/organization-types',
            },
        ],
    },
});

const editingType = ref<OrganizationType | null>(null);
const { name, slug, onSlugInput, initialize } = useAutoSlug();

const openEdit = (type: OrganizationType): void => {
    editingType.value = type;
};

watch(editingType, (type) => {
    if (type) {
        initialize(type.name, type.slug);
    }
});

const closeEdit = (): void => {
    editingType.value = null;
};

const organizationTypeActions = (type: OrganizationType): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        onClick: () => openEdit(type),
    },
    toggleIsActiveAction({
        url: `/settings/organization-types/${type.id}`,
        name: type.name,
        isActive: type.is_active ?? true,
        entityLabel: t('organization type'),
        t,
    }),
    {
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/settings/organization-types/${type.id}`,
        method: 'delete',
        disabled: (type.organizations_count ?? 0) > 0,
        confirm: {
            title: t('Delete organization type'),
            description: t('Are you sure you want to delete ":name"? This cannot be undone.', {
                name: type.name,
            }),
            confirmLabel: t('Delete'),
        },
    },
];
</script>

<template>
    <Head :title="t('Organization Types')" />

    <div class="space-y-6">
        <AlertError
            v-if="pageErrors.length"
            :errors="pageErrors"
            :title="t('Something went wrong.')"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tags class="size-5" />
                    {{ t('Types') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Configure client categories and classification colors') }}
                </CardDescription>
                <CardAction>
                    <SettingsAddButton href="/settings/organization-types/create">
                        {{ t('Add Type') }}
                    </SettingsAddButton>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div
                    v-if="organizationTypes.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No organization types configured.') }}
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="type in organizationTypes"
                        :key="type.id"
                        class="ui-list-row flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="size-4 rounded-full border"
                                :style="{
                                    backgroundColor: type.color ?? 'var(--primary)',
                                }"
                            />
                            <div>
                                <div class="font-medium">{{ type.name }}</div>
                                <p
                                    v-if="type.description"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ type.description }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-muted-foreground">
                                {{ type.organizations_count ?? 0 }} {{ t('organizations') }}
                            </span>
                            <RowActionsMenu
                                :actions="organizationTypeActions(type)"
                            />
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
                :action="`/settings/organization-types/${editingType.id}`"
                method="put"
                @success="closeEdit"
                v-slot="{ processing, errors }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit organization type') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Update the name, color, or description for :name.', { name: editingType.name }) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <AlertError
                        v-if="Object.keys(errors).length"
                        :errors="Object.values(errors)"
                        :title="t('Please fix the errors below.')"
                    />

                    <div class="grid gap-2">
                        <Label for="edit-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            v-model="name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-slug">{{ t('Slug') }}</Label>
                        <Input
                            id="edit-slug"
                            name="slug"
                            :model-value="slug"
                            @update:model-value="onSlugInput"
                        />
                        <p class="text-sm text-muted-foreground">
                            {{ t('Auto-generated from name. You can edit it.') }}
                        </p>
                        <InputError :message="errors.slug" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-color">{{ t('Color') }}</Label>
                        <Input
                            id="edit-color"
                            name="color"
                            type="color"
                            class="h-10 w-full cursor-pointer p-1"
                            :default-value="editingType.color ?? '#000000'"
                        />
                        <InputError :message="errors.color" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-description">{{ t('Description') }}</Label>
                        <Input
                            id="edit-description"
                            name="description"
                            :default-value="editingType.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeEdit"
                    >
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
