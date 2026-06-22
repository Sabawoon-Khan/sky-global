<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Pencil, Plus, Shield, Trash2 } from '@lucide/vue';
import { computed, ref, type Ref } from 'vue';
import RoleManagementController from '@/actions/App/Http/Controllers/Settings/RoleManagementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

interface Permission {
    id: number;
    name: string;
}

interface RoleRecord {
    id: number;
    name: string;
    users_count?: number;
    permissions?: Permission[];
}

interface Props {
    roles: RoleRecord[];
    permissions: Permission[];
}

const props = defineProps<Props>();

const { t } = useTranslations();

const protectedRoles = ['Owner'];
const createPermissions = ref<string[]>([]);
const editingRole = ref<RoleRecord | null>(null);
const editPermissions = ref<string[]>([]);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Roles', href: '/settings/roles' },
        ],
    },
});

const permissionGroups = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of props.permissions) {
        const [module] = permission.name.split('.');
        const key = module ?? permission.name;

        if (!groups[key]) {
            groups[key] = [];
        }

        groups[key].push(permission);
    }

    return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));
});

const togglePermission = (
    selected: Ref<string[]>,
    permissionName: string,
    checked: boolean,
): void => {
    if (checked) {
        if (!selected.value.includes(permissionName)) {
            selected.value = [...selected.value, permissionName];
        }

        return;
    }

    selected.value = selected.value.filter((name) => name !== permissionName);
};

const openEdit = (role: RoleRecord): void => {
    editingRole.value = role;
    editPermissions.value = role.permissions?.map((p) => p.name) ?? [];
};

const closeEdit = (): void => {
    editingRole.value = null;
    editPermissions.value = [];
};

const roleActions = (role: RoleRecord): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        onClick: () => openEdit(role),
    },
    {
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: RoleManagementController.destroy.url(role.id),
        method: 'delete',
        disabled:
            protectedRoles.includes(role.name) || (role.users_count ?? 0) > 0,
        confirm: {
            title: t('Delete role'),
            description: t('Are you sure you want to delete ":name"? This cannot be undone.', {
                name: role.name,
            }),
            confirmLabel: t('Delete'),
        },
    },
];

const formatModuleLabel = (module: string): string =>
    module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' ');
</script>

<template>
    <Head :title="t('Role Management')" />

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('Roles')"
            :description="t('Define roles and assign permissions to control access')"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Plus class="size-5" />
                    {{ t('Add Role') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Create a new role with specific permissions') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    action="/settings/roles"
                    method="post"
                    class="space-y-4 rounded-lg border p-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2 max-w-md">
                        <Label for="role-name">{{ t('Name') }} *</Label>
                        <Input
                            id="role-name"
                            name="name"
                            :placeholder="t('e.g. Project Lead')"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-4">
                        <Label>{{ t('Permissions') }}</Label>
                        <div
                            v-for="[module, modulePermissions] in permissionGroups"
                            :key="module"
                            class="rounded-lg border p-4"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ formatModuleLabel(module) }}
                            </p>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :model-value="
                                            createPermissions.includes(permission.name)
                                        "
                                        @update:model-value="
                                            (checked) =>
                                                togglePermission(
                                                    createPermissions,
                                                    permission.name,
                                                    checked === true,
                                                )
                                        "
                                    />
                                    <span>{{ permission.name.split('.').slice(1).join('.') || permission.name }}</span>
                                </label>
                            </div>
                        </div>
                        <input
                            v-for="permissionName in createPermissions"
                            :key="`create-${permissionName}`"
                            type="hidden"
                            name="permissions[]"
                            :value="permissionName"
                        />
                        <InputError :message="errors.permissions" />
                    </div>

                    <Button type="submit" :disabled="processing">
                        <Plus class="size-4" />
                        {{ t('Create Role') }}
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Shield class="size-5" />
                    {{ t('System Roles') }}
                </CardTitle>
                <CardDescription>
                    {{ roles.length }} {{ t('roles configured') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-if="roles.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No roles configured.') }}
                </div>

                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="flex items-center justify-between rounded-lg border px-4 py-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ role.name }}</span>
                            <Badge
                                v-if="protectedRoles.includes(role.name)"
                                variant="secondary"
                            >
                                {{ t('Protected') }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ role.permissions?.length ?? 0 }}
                            {{ t('permissions') }} ·
                            {{ role.users_count ?? 0 }}
                            {{ t('users') }}
                        </p>
                    </div>
                    <RowActionsMenu :actions="roleActions(role)" />
                </div>
            </CardContent>
        </Card>
    </div>

    <Dialog
        :open="editingRole !== null"
        @update:open="(open) => !open && closeEdit()"
    >
        <DialogContent v-if="editingRole" class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <Form
                :action="RoleManagementController.update.url(editingRole.id)"
                method="put"
                @success="closeEdit"
                v-slot="{ processing, errors }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit role') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Update the name and permissions for :name.', { name: editingRole.name }) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-role-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-role-name"
                            name="name"
                            :default-value="editingRole.name"
                            :disabled="protectedRoles.includes(editingRole.name)"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-4">
                        <Label>{{ t('Permissions') }}</Label>
                        <div
                            v-for="[module, modulePermissions] in permissionGroups"
                            :key="`edit-${module}`"
                            class="rounded-lg border p-4"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ formatModuleLabel(module) }}
                            </p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :model-value="
                                            editPermissions.includes(permission.name)
                                        "
                                        @update:model-value="
                                            (checked) =>
                                                togglePermission(
                                                    editPermissions,
                                                    permission.name,
                                                    checked === true,
                                                )
                                        "
                                    />
                                    <span>{{ permission.name.split('.').slice(1).join('.') || permission.name }}</span>
                                </label>
                            </div>
                        </div>
                        <input
                            v-for="permissionName in editPermissions"
                            :key="`edit-${permissionName}`"
                            type="hidden"
                            name="permissions[]"
                            :value="permissionName"
                        />
                        <InputError :message="errors.permissions" />
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
