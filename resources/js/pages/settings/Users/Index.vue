<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Search, Shield, UserCheck, UserCog, UserX } from '@lucide/vue';
import UserManagementController from '@/actions/App/Http/Controllers/Settings/UserManagementController';
import Heading from '@/components/Heading.vue';
import MisPagination from '@/components/MisPagination.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

interface Role {
    id: number;
    name: string;
}

interface UserRecord {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles?: Role[];
}

interface Props {
    users: Paginated<UserRecord>;
    roles: Role[];
    filters?: { search?: string | null };
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Users', href: '/settings/users' },
        ],
    },
});

const toggleUser = (user: UserRecord): void => {
    router.put(
        UserManagementController.update.url(user.id),
        { is_active: !user.is_active },
        { preserveScroll: true },
    );
};

const userActions = (user: UserRecord): RowActionItem[] => [
    {
        label: 'Edit roles',
        icon: Pencil,
        onClick: () => {
            document.getElementById(`roles-${user.id}`)?.focus();
        },
    },
    {
        label: user.is_active ? 'Disable account' : 'Enable account',
        icon: user.is_active ? UserX : UserCheck,
        separator: true,
        onClick: () => toggleUser(user),
    },
];
</script>

<template>
    <Head title="User Management" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Users"
            description="Assign roles and manage account access"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserCog class="size-5" />
                    System Users
                </CardTitle>
                <CardDescription>
                    {{ users.meta?.total ?? users.data.length }} registered users
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/settings/users"
                    class="relative max-w-sm"
                >
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search ?? ''"
                        placeholder="Search users..."
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="users.data.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No users found.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="user in users.data"
                        :key="user.id"
                        class="rounded-lg border p-4"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ user.name }}</span>
                                    <Badge
                                        :variant="
                                            user.is_active ? 'default' : 'destructive'
                                        "
                                    >
                                        {{ user.is_active ? 'Active' : 'Disabled' }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ user.email }}
                                </p>
                                <div
                                    v-if="user.roles?.length"
                                    class="mt-2 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        variant="secondary"
                                    >
                                        <Shield class="size-3" />
                                        {{ role.name }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:min-w-[220px]">
                                <div class="flex items-center justify-end">
                                    <RowActionsMenu :actions="userActions(user)" />
                                </div>
                                <Form
                                    v-bind="
                                        UserManagementController.update.form(user.id)
                                    "
                                    class="grid gap-2"
                                    :options="{ preserveScroll: true }"
                                    v-slot="{ processing }"
                                >
                                    <Label :for="`roles-${user.id}`">Roles</Label>
                                    <select
                                        :id="`roles-${user.id}`"
                                        name="roles[]"
                                        multiple
                                        class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="role.name"
                                            :selected="
                                                user.roles?.some(
                                                    (r) => r.name === role.name,
                                                )
                                            "
                                        >
                                            {{ role.name }}
                                        </option>
                                    </select>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="processing"
                                    >
                                        Update Roles
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                </div>

                <MisPagination :pagination="users" />
            </CardContent>
        </Card>
    </div>
</template>
