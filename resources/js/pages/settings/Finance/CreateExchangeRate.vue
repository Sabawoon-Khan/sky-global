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

defineProps<{
    currencies: string[];
}>();

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Currencies', href: '/settings/currencies' },
            { title: 'Create', href: '/settings/exchange-rates/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Add exchange rate')" />

    <div class="space-y-6">
        <Form
            action="/settings/exchange-rates"
            method="post"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Add exchange rate') }}</CardTitle>
                    <CardDescription>
                        {{ t('Define a conversion rate between two currencies') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="from-currency">{{ t('From currency') }}</Label>
                        <select
                            id="from-currency"
                            name="from_currency"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option value="" disabled selected>
                                {{ t('Select currency') }}
                            </option>
                            <option
                                v-for="code in currencies"
                                :key="`from-${code}`"
                                :value="code"
                            >
                                {{ code }}
                            </option>
                        </select>
                        <InputError :message="errors.from_currency" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="to-currency">{{ t('To currency') }}</Label>
                        <select
                            id="to-currency"
                            name="to_currency"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option value="" disabled selected>
                                {{ t('Select currency') }}
                            </option>
                            <option
                                v-for="code in currencies"
                                :key="`to-${code}`"
                                :value="code"
                            >
                                {{ code }}
                            </option>
                        </select>
                        <InputError :message="errors.to_currency" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="rate">{{ t('Rate') }}</Label>
                        <Input
                            id="rate"
                            name="rate"
                            type="number"
                            step="0.000001"
                            min="0.000001"
                            required
                        />
                        <InputError :message="errors.rate" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="effective-date">{{ t('Effective date') }}</Label>
                        <Input
                            id="effective-date"
                            name="effective_date"
                            type="date"
                            required
                        />
                        <InputError :message="errors.effective_date" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-3">
                <Button variant="outline" as-child>
                    <Link href="/settings/currencies">{{ t('Cancel') }}</Link>
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ t('Add exchange rate') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
