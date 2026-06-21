<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
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
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';

interface OrganizationType {
    id: number;
    name: string;
    color?: string | null;
    description?: string | null;
    is_active?: boolean;
    organizations_count?: number;
}

interface Props {
    organizationTypes: OrganizationType[];
}

defineProps<Props>();

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

const openEdit = (type: OrganizationType): void => {
    editingType.value = type;
};

const closeEdit = (): void => {
    editingType.value = null;
};

const organizationTypeActions = (type: OrganizationType): RowActionItem[] => [
    {
        label: 'Edit',
        icon: Pencil,
        onClick: () => openEdit(type),
    },
    toggleIsActiveAction({
        url: `/settings/organization-types/${type.id}`,
        name: type.name,
        isActive: type.is_active ?? true,
        entityLabel: 'organization type',
    }),
    {
        label: 'Delete',
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/settings/organization-types/${type.id}`,
        method: 'delete',
        disabled: (type.organizations_count ?? 0) > 0,
        confirm: {
            title: 'Delete organization type',
            description: `Are you sure you want to delete "${type.name}"? This cannot be undone.`,
            confirmLabel: 'Delete',
        },
    },
];
</script>

<template>
    <Head title="Organization Types" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Organization Types"
            description="Configure client categories and classification colors"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tags class="size-5" />
                    Types
                </CardTitle>
                <CardDescription>
                    {{ organizationTypes.length }} organization types
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <Form
                    action="/settings/organization-types"
                    method="post"
                    class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2"
                    v-slot="{ processing }"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            placeholder="e.g. Government"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="color">Color</Label>
                        <Input
                            id="color"
                            name="color"
                            type="color"
                            class="h-10 w-full cursor-pointer p-1"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            placeholder="Optional description"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="processing">
                            <Plus class="size-4" />
                            Add Type
                        </Button>
                    </div>
                </Form>

                <div
                    v-if="organizationTypes.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No organization types configured.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="type in organizationTypes"
                        :key="type.id"
                        class="flex items-center justify-between rounded-lg border px-4 py-3"
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
                                {{ type.organizations_count ?? 0 }} organizations
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
                    <DialogTitle>Edit organization type</DialogTitle>
                    <DialogDescription>
                        Update the name, color, or description for
                        {{ editingType.name }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-name">Name</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            :default-value="editingType.name"
                            required
                        />
                        <p
                            v-if="errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-color">Color</Label>
                        <Input
                            id="edit-color"
                            name="color"
                            type="color"
                            class="h-10 w-full cursor-pointer p-1"
                            :default-value="editingType.color ?? '#000000'"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-description">Description</Label>
                        <Input
                            id="edit-description"
                            name="description"
                            :default-value="editingType.description ?? ''"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeEdit"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Save changes
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
