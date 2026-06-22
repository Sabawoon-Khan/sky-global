<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Shield, UserCog } from '@lucide/vue';
import { computed } from 'vue';
import UserManagementController from '@/actions/App/Http/Controllers/Settings/UserManagementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { userActiveAction } from '@/lib/status-actions';

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

const { t } = useTranslations();
const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id as number | undefined);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Users', href: '/settings/users' },
        ],
    },
});

const userActions = (user: UserRecord): RowActionItem[] => {
    const actions: RowActionItem[] = [
        {
            label: t('Edit roles'),
            icon: Pencil,
            onClick: () => {
                document.getElementById(`roles-${user.id}`)?.focus();
            },
        },
    ];

    const activeAction = userActiveAction({
        url: UserManagementController.update.url(user.id),
        name: user.name,
        isActive: user.is_active,
        isCurrentUser: user.id === currentUserId.value,
    });

    if (activeAction) {
        actions.push(activeAction);
    }

    return actions;
};
</script>

<template>
    <Head :title="t('User Management')" />

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('Users')"
            :description="t('Create accounts, assign roles, and manage access')"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Plus class="size-5" />
                    {{ t('Add User') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Create a new system user with an initial password and roles') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    action="/settings/users"
                    method="post"
                    class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Name') }} *</Label>
                        <Input id="name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">{{ t('Email') }} *</Label>
                        <Input id="email" name="email" type="email" required />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">{{ t('Password') }} *</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">{{ t('Confirm password') }} *</Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="create-roles">{{ t('Roles') }}</Label>
                        <select
                            id="create-roles"
                            name="roles[]"
                            multiple
                            class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option v-for="role in roles" :key="role.id" :value="role.name">
                                {{ role.name }}
                            </option>
                        </select>
                        <InputError :message="errors.roles" />
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="processing">
                            <Plus class="size-4" />
                            {{ t('Create User') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserCog class="size-5" />
                    {{ t('System Users') }}
                </CardTitle>
                <CardDescription>
                    {{ users.meta?.total ?? users.data.length }}
                    {{ t('registered users') }}
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
                        :placeholder="t('Search users...')"
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="users.data.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No users found.') }}
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
                                        {{ user.is_active ? t('Active') : t('Disabled') }}
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
                                    :action="
                                        UserManagementController.update.url(user.id)
                                    "
                                    method="put"
                                    class="grid gap-2"
                                    :options="{ preserveScroll: true }"
                                    v-slot="{ processing }"
                                >
                                    <Label :for="`roles-${user.id}`">{{ t('Roles') }}</Label>
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
                                        {{ t('Update Roles') }}
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
