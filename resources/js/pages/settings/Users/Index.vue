<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Shield, UserCog } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';

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
    users: UserRecord[];
    roles: Role[];
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
    router.post(`/settings/users/${user.id}/toggle`, {}, { preserveScroll: true });
};
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
                    {{ users.length }} registered users
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="users.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No users found.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="user in users"
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
                                <Form
                                    :action="`/settings/users/${user.id}/roles`"
                                    method="post"
                                    class="grid gap-2"
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

                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="toggleUser(user)"
                                >
                                    {{
                                        user.is_active
                                            ? 'Disable Account'
                                            : 'Enable Account'
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
