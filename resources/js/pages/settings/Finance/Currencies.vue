<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRightLeft, Coins, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { toggleIsActiveAction } from '@/lib/status-actions';

interface CurrencyRecord {
    id: number;
    code: string;
    name: string;
    symbol: string | null;
    is_active: boolean;
    is_default: boolean;
}

interface ExchangeRateRecord {
    id: number;
    from_currency: string;
    to_currency: string;
    rate: number;
    effective_date: string | null;
}

const props = defineProps<{
    currencies: CurrencyRecord[];
    exchangeRates: ExchangeRateRecord[];
}>();

const { t } = useTranslations();

const editingCurrency = ref<CurrencyRecord | null>(null);
const editingRate = ref<ExchangeRateRecord | null>(null);

const currencyOptions = computed(() => props.currencies.map((currency: CurrencyRecord) => currency.code));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Currencies', href: '/settings/currencies' },
        ],
    },
});

const openCurrencyEdit = (currency: CurrencyRecord): void => {
    editingCurrency.value = currency;
};

const closeCurrencyEdit = (): void => {
    editingCurrency.value = null;
};

const openRateEdit = (rate: ExchangeRateRecord): void => {
    editingRate.value = rate;
};

const closeRateEdit = (): void => {
    editingRate.value = null;
};

const currencyActions = (currency: CurrencyRecord): RowActionItem[] => {
    const actions: RowActionItem[] = [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openCurrencyEdit(currency),
        },
        toggleIsActiveAction({
            url: `/settings/currencies/${currency.id}`,
            name: currency.code,
            isActive: currency.is_active,
            entityLabel: t('currency'),
            t,
        }),
    ];

    if (currency.is_default) {
        return actions;
    }

    actions.push({
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/settings/currencies/${currency.id}`,
        method: 'delete',
        confirm: {
            title: t('Delete currency'),
            description: t('Are you sure you want to delete ":name"?', {
                name: currency.code,
            }),
            confirmLabel: t('Delete'),
        },
    });

    return actions;
};

const rateActions = (rate: ExchangeRateRecord): RowActionItem[] => [
    {
        label: t('Edit'),
        icon: Pencil,
        onClick: () => openRateEdit(rate),
    },
    {
        label: t('Delete'),
        icon: Trash2,
        variant: 'destructive',
        separator: true,
        href: `/settings/exchange-rates/${rate.id}`,
        method: 'delete',
        confirm: {
            title: t('Delete exchange rate'),
            description: t('Delete :from → :to for :date?', {
                from: rate.from_currency,
                to: rate.to_currency,
                date: rate.effective_date ?? '-',
            }),
            confirmLabel: t('Delete'),
        },
    },
];
</script>

<template>
    <Head :title="t('Currencies')" />

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Coins class="size-5" />
                    {{ t('Currencies') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Manage currency codes, symbols, and default settings') }}
                </CardDescription>
                <CardAction>
                    <SettingsAddButton href="/settings/currencies/create">
                        {{ t('Add currency') }}
                    </SettingsAddButton>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div
                    v-if="props.currencies.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No currencies configured.') }}
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="currency in props.currencies"
                        :key="currency.id"
                        class="ui-list-row flex items-center justify-between px-4 py-3"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">
                                    {{ currency.code }} · {{ currency.name }}
                                </span>
                                <Badge v-if="currency.is_default" variant="secondary">
                                    {{ t('Default') }}
                                </Badge>
                                <Badge v-if="currency.is_active === false" variant="outline">
                                    {{ t('Inactive') }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ currency.symbol || '-' }}
                            </p>
                        </div>
                        <RowActionsMenu :actions="currencyActions(currency)" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ArrowRightLeft class="size-5" />
                    {{ t('Exchange Rates') }}
                </CardTitle>
                <CardDescription>
                    {{ t('Set conversion rates between currencies by effective date') }}
                </CardDescription>
                <CardAction>
                    <SettingsAddButton href="/settings/exchange-rates/create">
                        {{ t('Add exchange rate') }}
                    </SettingsAddButton>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div
                    v-if="props.exchangeRates.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No exchange rates configured.') }}
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="rate in props.exchangeRates"
                        :key="rate.id"
                        class="ui-list-row flex items-center justify-between px-4 py-3"
                    >
                        <div>
                            <div class="font-medium">
                                {{ rate.from_currency }} → {{ rate.to_currency }}
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ t('Rate') }}: {{ rate.rate }} ·
                                {{ rate.effective_date ?? '-' }}
                            </p>
                        </div>
                        <RowActionsMenu :actions="rateActions(rate)" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <Dialog
        :open="editingCurrency !== null"
        @update:open="(open) => !open && closeCurrencyEdit()"
    >
        <DialogContent v-if="editingCurrency">
            <Form
                :action="`/settings/currencies/${editingCurrency.id}`"
                method="put"
                @success="closeCurrencyEdit"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit currency') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Update currency details for :name.', { name: editingCurrency.code }) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-currency-code">{{ t('Code') }}</Label>
                        <Input
                            id="edit-currency-code"
                            name="code"
                            maxlength="3"
                            :default-value="editingCurrency.code"
                            required
                        />
                        <InputError :message="errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-currency-name">{{ t('Name') }}</Label>
                        <Input
                            id="edit-currency-name"
                            name="name"
                            :default-value="editingCurrency.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-currency-symbol">{{ t('Symbol') }}</Label>
                        <Input
                            id="edit-currency-symbol"
                            name="symbol"
                            :default-value="editingCurrency.symbol ?? ''"
                        />
                        <InputError :message="errors.symbol" />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input
                                type="hidden"
                                name="is_active"
                                value="0"
                            />
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                :checked="editingCurrency.is_active"
                                class="size-4 rounded border-input"
                            />
                            {{ t('Active') }}
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input
                                type="hidden"
                                name="is_default"
                                value="0"
                            />
                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                                :checked="editingCurrency.is_default"
                                class="size-4 rounded border-input"
                            />
                            {{ t('Default currency') }}
                        </label>
                    </div>
                    <InputError :message="errors.currency" />
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="closeCurrencyEdit">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('Save changes') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="editingRate !== null"
        @update:open="(open) => !open && closeRateEdit()"
    >
        <DialogContent v-if="editingRate">
            <Form
                :action="`/settings/exchange-rates/${editingRate.id}`"
                method="put"
                @success="closeRateEdit"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>{{ t('Edit exchange rate') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Update the selected exchange rate record.') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-from-currency">{{ t('From currency') }}</Label>
                        <select
                            id="edit-from-currency"
                            name="from_currency"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option
                                v-for="code in currencyOptions"
                                :key="`edit-from-${code}`"
                                :value="code"
                                :selected="code === editingRate.from_currency"
                            >
                                {{ code }}
                            </option>
                        </select>
                        <InputError :message="errors.from_currency" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-to-currency">{{ t('To currency') }}</Label>
                        <select
                            id="edit-to-currency"
                            name="to_currency"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            required
                        >
                            <option
                                v-for="code in currencyOptions"
                                :key="`edit-to-${code}`"
                                :value="code"
                                :selected="code === editingRate.to_currency"
                            >
                                {{ code }}
                            </option>
                        </select>
                        <InputError :message="errors.to_currency" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-rate">{{ t('Rate') }}</Label>
                        <Input
                            id="edit-rate"
                            name="rate"
                            type="number"
                            step="0.000001"
                            min="0.000001"
                            :default-value="editingRate.rate"
                            required
                        />
                        <InputError :message="errors.rate" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-effective-date">{{ t('Effective date') }}</Label>
                        <Input
                            id="edit-effective-date"
                            name="effective_date"
                            type="date"
                            :default-value="editingRate.effective_date ?? ''"
                            required
                        />
                        <InputError :message="errors.effective_date" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="secondary" @click="closeRateEdit">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('Save changes') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
