<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Pencil, Plus, Shield, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import RoleManagementController from '@/actions/App/Http/Controllers/Settings/RoleManagementController';
import Heading from '@/components/Heading.vue';
import Can from '@/components/Can.vue';
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
const editingRole = ref<RoleRecord | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Roles', href: '/settings/roles' },
        ],
    },
});

const permissionVerbOrder = [
    'view',
    'create',
    'edit',
    'delete',
    'archive',
    'view_competitors',
    'manage_users',
];

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

    return Object.entries(groups)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([module, modulePermissions]) => [
            module,
            [...modulePermissions].sort((a, b) => {
                const verbA = a.name.split('.').slice(1).join('.');
                const verbB = b.name.split('.').slice(1).join('.');
                const orderA = permissionVerbOrder.indexOf(verbA);
                const orderB = permissionVerbOrder.indexOf(verbB);

                if (orderA === -1 && orderB === -1) {
                    return verbA.localeCompare(verbB);
                }

                if (orderA === -1) {
                    return 1;
                }

                if (orderB === -1) {
                    return -1;
                }

                return orderA - orderB;
            }),
        ] as [string, Permission[]]);
});

const openEdit = (role: RoleRecord): void => {
    editingRole.value = role;
};

const closeEdit = (): void => {
    editingRole.value = null;
};

const roleHasPermission = (role: RoleRecord, permissionName: string): boolean =>
    role.permissions?.some((permission) => permission.name === permissionName) ??
    false;

const formatPermissionLabel = (permissionName: string): string => {
    const [module, ...verbParts] = permissionName.split('.');
    const verb = verbParts.join('.') || permissionName;

    if (verb === 'archive' && module === 'archive') {
        return t('Move to long-term archive');
    }

    if (verb === 'archive') {
        return t('Mark as archived');
    }

    const labels: Record<string, string> = {
        view: t('View'),
        create: t('Create'),
        edit: t('Edit'),
        delete: t('Delete'),
        view_competitors: t('View competitors'),
        manage_users: t('Manage users'),
    };

    return labels[verb] ?? verb.replace(/_/g, ' ');
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
            description: t(
                'Are you sure you want to delete ":name"? This cannot be undone.',
                {
                    name: role.name,
                },
            ),
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
            :description="
                t('Define roles and assign permissions to control access')
            "
        />

        <Can permission="settings.manage_users">
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
                    <div class="grid max-w-md gap-2">
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
                            v-for="[
                                module,
                                modulePermissions,
                            ] in permissionGroups"
                            :key="module"
                            class="rounded-lg border p-4"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ formatModuleLabel(module) }}
                            </p>
                            <div
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        :id="`create-perm-${permission.id}`"
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.name"
                                        class="size-4 rounded border border-input accent-primary"
                                    />
                                    <Label
                                        :for="`create-perm-${permission.id}`"
                                        class="cursor-pointer font-normal"
                                    >
                                        {{ formatPermissionLabel(permission.name) }}
                                    </Label>
                                </div>
                            </div>
                        </div>
                        <InputError :message="errors.permissions" />
                    </div>

                    <Button type="submit" :disabled="processing">
                        <Plus class="size-4" />
                        {{ t('Create Role') }}
                    </Button>
                </Form>
            </CardContent>
        </Card>
        </Can>

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
        <DialogContent
            v-if="editingRole"
            class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
        >
            <Form
                :key="editingRole.id"
                :action="RoleManagementController.update.url(editingRole.id)"
                method="put"
                @success="closeEdit"
                v-slot="{ processing, errors }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit role') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t('Update the name and permissions for :name.', {
                                name: editingRole.name,
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-role-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-role-name"
                            name="name"
                            :default-value="editingRole.name"
                            :disabled="
                                protectedRoles.includes(editingRole.name)
                            "
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-4">
                        <Label>{{ t('Permissions') }}</Label>
                        <div
                            v-for="[
                                module,
                                modulePermissions,
                            ] in permissionGroups"
                            :key="`edit-${module}`"
                            class="rounded-lg border p-4"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ formatModuleLabel(module) }}
                            </p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        :id="`edit-perm-${editingRole.id}-${permission.id}`"
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.name"
                                        :defaultChecked="
                                            roleHasPermission(
                                                editingRole,
                                                permission.name,
                                            )
                                        "
                                        class="size-4 rounded border border-input accent-primary"
                                    />
                                    <Label
                                        :for="`edit-perm-${editingRole.id}-${permission.id}`"
                                        class="cursor-pointer font-normal"
                                    >
                                        {{ formatPermissionLabel(permission.name) }}
                                    </Label>
                                </div>
                            </div>
                        </div>
                        <InputError :message="errors.permissions" />
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
