<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

interface Permission {
    id: number;
    name: string;
}

const props = defineProps<{
    permissions: Permission[];
}>();

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Roles', href: '/settings/roles' },
            { title: 'Create', href: '/settings/roles/create' },
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
    'view_login_logs',
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

const formatPermissionLabel = (permissionName: string): string => {
    const [module, ...verbParts] = permissionName.split('.');
    const verb = verbParts.join('.') || permissionName;

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
        view_login_logs: t('View login logs'),
    };

    return labels[verb] ?? verb.replace(/_/g, ' ');
};

const formatModuleLabel = (module: string): string =>
    module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' ');
</script>

<template>
    <Head :title="t('Add Role')" />

    <div class="space-y-6">
        <Form
            action="/settings/roles"
            method="post"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add Role') }}</CardTitle>
                    <CardDescription>
                        {{ t('Create a new role with specific permissions') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="role-name">{{ t('Name') }} *</Label>
                        <Input id="role-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-4">
                        <Label>{{ t('Permissions') }}</Label>
                        <div
                            v-for="[module, modulePermissions] in permissionGroups"
                            :key="module"
                            class="ui-inset-panel"
                        >
                            <p class="mb-3 text-sm font-medium">
                                {{ formatModuleLabel(module) }}
                            </p>
                            <div
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
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
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/roles">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Create Role') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
