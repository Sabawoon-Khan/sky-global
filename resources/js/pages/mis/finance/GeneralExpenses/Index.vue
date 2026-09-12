<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import MisPagination from '@/components/MisPagination.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { V2Hero, V2ListPage, V2StatCard, V2StatGrid } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate, type Paginated } from '@/lib/format';
import { cn } from '@/lib/utils';
import { ChevronDown, Paperclip, Plus, Receipt } from '@lucide/vue';

interface FinanceAttachment {
    id: number;
    original_filename: string;
    download_url: string;
}

interface GeneralRecord {
    id: number;
    description: string | null;
    category?: string | null;
    amount: number;
    amount_usd?: number | null;
    currency?: string | null;
    transaction_date?: string | null;
    status?: string | null;
    attachments?: FinanceAttachment[];
}

const props = defineProps<{
    generalExpenses: Paginated<GeneralRecord>;
    stats?: { total?: number; count?: number };
}>();

const { t } = useMisPage();
const showGeneralExpenseForm = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finance', href: '/finance' },
            {
                title: 'Overhead & Salaries',
                href: '/finance/general-expenses',
            },
        ],
    },
});

const money = (value?: number | null): string => formatAfn(value);
</script>

<template>
    <Head :title="t('Overhead & Salaries')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-dashboard.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Overhead & Salaries') }}</template>
            <template #description>
                {{ t('Office rent, salaries, utilities, and other overhead.') }}
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        icon-tone="warm"
                        :title="t('Records')"
                        :value="
                            String(stats?.count ?? generalExpenses.total ?? 0)
                        "
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="orange"
                        :title="t('Total')"
                        :value="formatAfn(stats?.total)"
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <Card>
            <Collapsible v-model:open="showGeneralExpenseForm">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">{{
                                t('Overhead & Salaries')
                            }}</CardTitle>
                        </div>
                        <Can permission="finance.create">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Plus class="me-1 size-4" />
                                    {{ t('Add') }}
                                    <ChevronDown
                                        class="ms-1 size-4 transition-transform"
                                        :class="
                                            cn(
                                                showGeneralExpenseForm &&
                                                    'rotate-180',
                                            )
                                        "
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Can>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Can permission="finance.create">
                        <CollapsibleContent
                            class="rounded-md border bg-muted/20 p-4"
                        >
                            <Form
                                action="/finance/general-expenses"
                                method="post"
                                class="grid gap-3 sm:grid-cols-2"
                                :options="{
                                    preserveScroll: true,
                                    resetOnSuccess: true,
                                    forceFormData: true,
                                }"
                                validate-files
                                v-slot="{ errors, processing }"
                            >
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="ge-description">{{
                                        t('Description')
                                    }}</Label>
                                    <Textarea
                                        id="ge-description"
                                        name="description"
                                        rows="2"
                                        required
                                    />
                                    <InputError :message="errors.description" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-category">{{
                                        t('Category')
                                    }}</Label>
                                    <select
                                        id="ge-category"
                                        name="category"
                                        class="h-9 rounded-md border border-input px-3 text-sm"
                                    >
                                        <option value="">
                                            {{ t('Select category') }}
                                        </option>
                                        <option value="rent">
                                            {{ t('Office Rent') }}
                                        </option>
                                        <option value="salary">
                                            {{ t('Salary') }}
                                        </option>
                                        <option value="utilities">
                                            {{ t('Utilities') }}
                                        </option>
                                        <option value="equipment">
                                            {{ t('Equipment') }}
                                        </option>
                                        <option value="other">
                                            {{ t('Other') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-amount"
                                        >{{ t('Amount') }} *</Label
                                    >
                                    <Input
                                        id="ge-amount"
                                        name="amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="ge-date"
                                        >{{ t('Date') }} *</Label
                                    >
                                    <Input
                                        id="ge-date"
                                        name="transaction_date"
                                        type="date"
                                        required
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <OptionalAttachmentField
                                        :label="t('Receipt')"
                                        :error="errors.attachment"
                                    />
                                </div>
                                <div class="flex items-end sm:col-span-2">
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="processing"
                                    >
                                        {{ t('Save') }}
                                    </Button>
                                </div>
                            </Form>
                        </CollapsibleContent>
                    </Can>
                    <div
                        v-if="!props.generalExpenses.data.length"
                        class="ui-empty-state"
                    >
                        {{ t('No overhead records.') }}
                    </div>
                    <div v-else class="space-y-0">
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b bg-muted/40 text-start text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Description') }}
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Category') }}
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Date') }}
                                        </th>
                                        <th class="px-3 py-2 font-medium">
                                            {{ t('Attachment') }}
                                        </th>
                                        <th
                                            class="px-3 py-2 text-end font-medium"
                                        >
                                            {{ t('Amount') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="item in props.generalExpenses
                                            .data"
                                        :key="item.id"
                                        class="hover:bg-muted/30"
                                    >
                                        <td class="px-3 py-2">
                                            {{ item.description }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{ item.category ?? '—' }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    item.transaction_date,
                                                )
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <a
                                                v-if="item.attachments?.length"
                                                :href="
                                                    item.attachments[0]
                                                        .download_url
                                                "
                                                class="inline-flex items-center gap-1 text-primary hover:underline"
                                                :title="
                                                    item.attachments[0]
                                                        .original_filename
                                                "
                                            >
                                                <Paperclip
                                                    class="size-3.5 shrink-0"
                                                />
                                                <span
                                                    class="max-w-[8rem] truncate text-xs"
                                                >
                                                    {{
                                                        item.attachments[0]
                                                            .original_filename
                                                    }}
                                                </span>
                                            </a>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >—</span
                                            >
                                        </td>
                                        <td
                                            class="px-3 py-2 text-end font-medium text-destructive"
                                        >
                                            {{ money(item.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t px-4 py-3">
                            <MisPagination
                                :pagination="props.generalExpenses"
                            />
                        </div>
                    </div>
                </CardContent>
            </Collapsible>
        </Card>
    </V2ListPage>
</template>
