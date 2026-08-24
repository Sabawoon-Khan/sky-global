<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { KeyRound, Pencil, Search, Shield, UserCog } from '@lucide/vue';
import { computed } from 'vue';
import UserManagementController from '@/actions/App/Http/Controllers/Settings/UserManagementController';
import StatusChangeHistory, {
    type StatusChangeLogRecord,
} from '@/components/StatusChangeHistory.vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPagination from '@/components/MisPagination.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
    status_change_logs?: StatusChangeLogRecord[];
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
                document
                    .getElementById(`user-roles-${user.id}`)
                    ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            },
        },
        {
            label: t('Change password'),
            icon: KeyRound,
            onClick: () => {
                document
                    .getElementById(`user-password-${user.id}`)
                    ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                document
                    .getElementById(`password-${user.id}`)
                    ?.focus();
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
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserCog class="size-5" />
                    {{ t('System Users') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Manage user accounts, roles, and access') }}
                </CardDescription>
                <CardAction>
                    <Can permission="settings.manage_users">
                        <SettingsAddButton href="/settings/users/create">
                            {{ t('Add User') }}
                        </SettingsAddButton>
                    </Can>
                </CardAction>
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
                        :aria-label="t('Search users...')"
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="users.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
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
                                    :id="`user-roles-${user.id}`"
                                    :action="
                                        UserManagementController.update.url(user.id)
                                    "
                                    method="put"
                                    class="grid gap-2"
                                    :options="{ preserveScroll: true }"
                                    v-slot="{ processing }"
                                >
                                    <Label>{{ t('Roles') }}</Label>
                                    <div
                                        class="grid gap-2 rounded-md border border-input p-3"
                                    >
                                        <div
                                            v-for="role in roles"
                                            :key="role.id"
                                            class="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                :id="`roles-${user.id}-${role.id}`"
                                                type="checkbox"
                                                name="roles[]"
                                                :value="role.name"
                                                :defaultChecked="
                                                    user.roles?.some(
                                                        (assignedRole) =>
                                                            assignedRole.name ===
                                                            role.name,
                                                    )
                                                "
                                                class="size-4 rounded border border-input accent-primary"
                                            />
                                            <Label
                                                :for="`roles-${user.id}-${role.id}`"
                                                class="cursor-pointer font-normal"
                                            >
                                                {{ role.name }}
                                            </Label>
                                        </div>
                                    </div>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="processing"
                                    >
                                        {{ t('Update Roles') }}
                                    </Button>
                                </Form>
                                <Form
                                    :id="`user-password-${user.id}`"
                                    :action="
                                        UserManagementController.update.url(user.id)
                                    "
                                    method="put"
                                    class="grid gap-2"
                                    :options="{ preserveScroll: true }"
                                    reset-on-success
                                    :reset-on-error="[
                                        'password',
                                        'password_confirmation',
                                    ]"
                                    v-slot="{ errors, processing }"
                                >
                                    <Label :for="`password-${user.id}`">
                                        {{ t('Change password') }}
                                    </Label>
                                    <PasswordInput
                                        :id="`password-${user.id}`"
                                        name="password"
                                        autocomplete="new-password"
                                        :placeholder="t('New password')"
                                    />
                                    <InputError :message="errors.password" />
                                    <PasswordInput
                                        :id="`password_confirmation-${user.id}`"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        :placeholder="t('Confirm password')"
                                    />
                                    <InputError
                                        :message="errors.password_confirmation"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="outline"
                                        :disabled="processing"
                                    >
                                        {{ t('Update Password') }}
                                    </Button>
                                </Form>
                            </div>
                        </div>

                        <StatusChangeHistory
                            v-if="user.status_change_logs?.length"
                            class="mt-4"
                            :logs="user.status_change_logs"
                        />
                    </div>
                </div>

                <MisPagination :pagination="users" />
            </CardContent>
        </Card>
    </div>
</template>
