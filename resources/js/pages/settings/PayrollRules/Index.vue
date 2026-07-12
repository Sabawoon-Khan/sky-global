<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
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

interface PayrollSettings {
    working_days_per_month: number;
    absent_deduction_percent: number;
    sick_leave_pay_percent: number;
    annual_leave_pay_percent: number;
    casual_leave_pay_percent: number;
    other_leave_pay_percent: number;
}

interface Props {
    settings: PayrollSettings;
}

defineProps<Props>();

const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Payroll Rules', href: '/settings/payroll-rules' },
        ],
    },
});

const ruleFields = [
    {
        key: 'working_days_per_month',
        label: t('Working days per month'),
        description: t('Used to calculate daily rate from monthly salary.'),
        min: 1,
        max: 31,
    },
    {
        key: 'absent_deduction_percent',
        label: t('Absent day deduction'),
        description: t('Extra deduction per absent day (% of daily rate). 100 = full day deducted.'),
        min: 0,
        max: 200,
    },
    {
        key: 'sick_leave_pay_percent',
        label: t('Sick leave pay'),
        description: t('100 = fully paid, 0 = unpaid.'),
        min: 0,
        max: 100,
    },
    {
        key: 'annual_leave_pay_percent',
        label: t('Annual leave pay'),
        description: t('100 = fully paid, 0 = unpaid.'),
        min: 0,
        max: 100,
    },
    {
        key: 'casual_leave_pay_percent',
        label: t('Casual leave pay'),
        description: t('100 = fully paid, 0 = unpaid.'),
        min: 0,
        max: 100,
    },
    {
        key: 'other_leave_pay_percent',
        label: t('Other leave pay'),
        description: t('100 = fully paid, 0 = unpaid.'),
        min: 0,
        max: 100,
    },
] as const;
</script>

<template>
    <Head :title="t('Payroll Rules')" />

    <div class="space-y-6">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-medium">
                <Settings2 class="size-5" />
                {{ t('Payroll Rules') }}
            </h2>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'Configure how absences, leave types, and deductions are calculated when generating payroll from attendance.',
                    )
                }}
            </p>
        </div>

        <Form
            action="/settings/payroll-rules"
            method="put"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('Attendance-based calculation') }}</CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'Payroll is calculated as: daily rate × paid days, minus absence deductions. Daily rate = monthly salary ÷ working days.',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6 sm:grid-cols-2">
                    <div
                        v-for="field in ruleFields"
                        :key="field.key"
                        class="grid gap-2"
                    >
                        <Label :for="field.key">
                            {{ field.label }}
                            <span v-if="field.key !== 'working_days_per_month'"> (%)</span>
                        </Label>
                        <Input
                            :id="field.key"
                            :name="field.key"
                            type="number"
                            :min="field.min"
                            :max="field.max"
                            required
                            :default-value="settings[field.key]"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ field.description }}
                        </p>
                        <InputError :message="errors[field.key]" />
                    </div>
                </CardContent>
            </Card>

            <Button type="submit" :disabled="processing">
                {{ t('Save rules') }}
            </Button>
        </Form>
    </div>
</template>
