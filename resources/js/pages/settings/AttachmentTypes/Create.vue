<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Form Types', href: '/settings/form-types' },
            { title: 'Create', href: '/settings/form-types/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add form type')" />

    <div class="space-y-6">
        <Form
            action="/settings/form-types"
            method="post"
            class="space-y-6"
            v-slot="{ processing, errors }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add form type') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Name') }}</Label>
                        <Input id="name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="requires_expiry"
                            name="requires_expiry"
                            type="checkbox"
                            value="1"
                            class="size-4 rounded border-input"
                        />
                        <Label for="requires_expiry" class="font-normal">
                            {{ t('Requires expiry date') }}
                        </Label>
                    </div>
                    <div class="grid gap-2">
                        <Label for="sort_order">{{ t('Sort order') }}</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            default-value="0"
                        />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/form-types">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Add form type') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
