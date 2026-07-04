<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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

interface Role {
    id: number;
    name: string;
}

defineProps<{
    roles: Role[];
}>();

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Users', href: '/settings/users' },
            { title: 'Create', href: '/settings/users/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add User')" />

    <div class="space-y-6">
        <Form
            action="/settings/users"
            method="post"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add User') }}</CardTitle>
                    <CardDescription>
                        {{ t('Create a new user account and assign roles') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
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
                        <Label for="password_confirmation">
                            {{ t('Confirm password') }} *
                        </Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>{{ t('Roles') }}</Label>
                        <div
                            class="grid gap-2 rounded-md border border-input p-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                v-for="role in roles"
                                :key="role.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    :id="`create-role-${role.id}`"
                                    type="checkbox"
                                    name="roles[]"
                                    :value="role.name"
                                    class="size-4 rounded border border-input accent-primary"
                                />
                                <Label
                                    :for="`create-role-${role.id}`"
                                    class="cursor-pointer font-normal"
                                >
                                    {{ role.name }}
                                </Label>
                            </div>
                        </div>
                        <InputError :message="errors.roles" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/users">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Create User') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
