<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AlertError from '@/components/AlertError.vue';
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
import { useAutoSlug } from '@/composables/useAutoSlug';

const { t } = useTranslations();
const { name, slug, onSlugInput } = useAutoSlug();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            {
                title: 'Organization Types',
                href: '/settings/organization-types',
            },
            { title: 'Create', href: '/settings/organization-types/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add Type')" />

    <div class="space-y-6">
        <Form
            action="/settings/organization-types"
            method="post"
            class="space-y-6"
            v-slot="{ processing, errors }"
        >
            <AlertError
                v-if="Object.keys(errors).length"
                :errors="Object.values(errors)"
                :title="t('Please fix the errors below.')"
            />

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add Type') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Name') }}</Label>
                        <Input id="name" name="name" v-model="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="slug">{{ t('Slug') }}</Label>
                        <Input
                            id="slug"
                            name="slug"
                            :model-value="slug"
                            @update:model-value="onSlugInput"
                        />
                        <p class="text-sm text-muted-foreground">
                            {{ t('Auto-generated from name. You can edit it.') }}
                        </p>
                        <InputError :message="errors.slug" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="color">{{ t('Color') }}</Label>
                        <Input
                            id="color"
                            name="color"
                            type="color"
                            class="h-10 w-full max-w-xs cursor-pointer p-1"
                        />
                        <InputError :message="errors.color" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">{{ t('Description') }}</Label>
                        <Input id="description" name="description" />
                        <InputError :message="errors.description" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/organization-types">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Add Type') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
