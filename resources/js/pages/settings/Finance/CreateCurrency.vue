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

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Currencies', href: '/settings/currencies' },
            { title: 'Create', href: '/settings/currencies/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add currency')" />

    <div class="space-y-6">
        <Form
            action="/settings/currencies"
            method="post"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add currency') }}</CardTitle>
                    <CardDescription>
                        {{ t('Add a new currency to the system') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="currency-code">{{ t('Code') }}</Label>
                        <Input
                            id="currency-code"
                            name="code"
                            maxlength="3"
                            required
                        />
                        <InputError :message="errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency-name">{{ t('Name') }}</Label>
                        <Input id="currency-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency-symbol">{{ t('Symbol') }}</Label>
                        <Input id="currency-symbol" name="symbol" />
                        <InputError :message="errors.symbol" />
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0" />
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                checked
                                class="size-4 rounded border-input"
                            />
                            {{ t('Active') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_default" value="0" />
                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                                class="size-4 rounded border-input"
                            />
                            {{ t('Default currency') }}
                        </label>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/currencies">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Add currency') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
