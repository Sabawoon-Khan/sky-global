<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Award,
    Building2,
    CheckCircle2,
    Circle,
    GraduationCap,
    Printer,
    Shield,
    UserPlus,
} from '@lucide/vue';
import Can from '@/components/Can.vue';
import FileLink from '@/components/FileLink.vue';
import InputError from '@/components/InputError.vue';
import MisTabs from '@/components/MisTabs.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import StatusChangeHistory, {
    type StatusChangeLogRecord,
} from '@/components/StatusChangeHistory.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { formatAfn, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { computed, ref } from 'vue';

interface TrainingGuard {
    id: number;
    name: string;
    father_name: string;
    grandfather_name?: string | null;
    tazkira_number?: string | null;
    id_card_number?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    batch_number: string;
    status: string;
    training_path?: string | null;
    ministry_period_start?: string | null;
    ministry_period_end?: string | null;
    fee_number?: string | null;
    fee_amount?: number | string | null;
    company_trainer?: string | null;
    company_location?: string | null;
    certificate_number?: string | null;
    certificate_issued_at?: string | null;
    certificate_url?: string | null;
    certificate_original_filename?: string | null;
    notes?: string | null;
    employee_id?: number | null;
    contractor_id?: number | null;
    employee?: { id: number; name: string } | null;
    contractor?: { id: number; name: string } | null;
    ministry_payment?: {
        id: number;
        reference_number: string;
        total_amount: number | string;
    } | null;
    created_by?: { id: number; name: string } | null;
    status_change_logs?: StatusChangeLogRecord[];
}

const props = defineProps<{
    guard: TrainingGuard;
    next_certificate_number?: string;
}>();

const { t, can } = useMisPage();

type TabId = 'overview' | 'training' | 'certificate' | 'history';

const activeTab = ref<TabId>(
    props.guard.status === 'registered' ? 'overview' : 'training',
);

const tabs = computed(() => {
    const items: { id: TabId; label: string }[] = [
        { id: 'overview', label: t('Overview') },
        { id: 'training', label: t('Training') },
        { id: 'certificate', label: t('Certificate of completion') },
        { id: 'history', label: t('Activation log') },
    ];

    return items;
});

const status = computed(() => props.guard.status);
const isRegistered = computed(() => status.value === 'registered');
const inTraining = computed(
    () => status.value === 'ministry' || status.value === 'company',
);
const isCompleted = computed(() => status.value === 'completed');
const isCertified = computed(() => status.value === 'certified');
const canCertify = computed(() =>
    ['ministry', 'company', 'completed'].includes(status.value),
);

const pathLabel = (path?: string | null): string => {
    if (path === 'ministry') {
        return t('Public Protection Deputy');
    }
    if (path === 'company') {
        return t('Company training');
    }
    return t('Not assigned');
};

const pipelineSteps = computed(() => [
    { key: 'registered', label: t('registered'), done: true },
    {
        key: 'training',
        label:
            props.guard.training_path === 'ministry'
                ? t('ministry')
                : props.guard.training_path === 'company'
                  ? t('company')
                  : t('Training'),
        done: inTraining.value || isCompleted.value || isCertified.value,
        active: inTraining.value,
    },
    {
        key: 'completed',
        label: t('Completed'),
        done: isCompleted.value || isCertified.value,
        active: isCompleted.value,
    },
    {
        key: 'certified',
        label: t('certified'),
        done: isCertified.value,
        active: isCertified.value,
    },
]);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Training', href: '/training/guards' },
            { title: 'Guard', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="guard.name" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-people.png">
            <template #eyebrow>{{ t('Education and Training') }}</template>
            <template #title>{{ guard.name }}</template>
            <template #description>
                {{ guard.father_name }}
                <span v-if="guard.grandfather_name">
                    · {{ guard.grandfather_name }}
                </span>
                · {{ t('Batch number') }} {{ guard.batch_number }}
            </template>
            <template #actions>
                <StatusBadge :status="guard.status" />
                <Button variant="outline" as-child>
                    <Link href="/training/guards">{{ t('Back to list') }}</Link>
                </Button>
                <Button v-if="can('training.edit')" as-child>
                    <Link :href="`/training/guards/${guard.id}/edit`">
                        {{ t('Edit') }}
                    </Link>
                </Button>
                <Button v-if="isCertified" variant="default" as-child>
                    <Link
                        :href="`/training/guards/${guard.id}/certificate/print`"
                        target="_blank"
                    >
                        <Printer class="me-2 size-4" />
                        {{ t('Print certificate') }}
                    </Link>
                </Button>
                <Form
                    v-if="(isCompleted || isCertified) && !guard.employee_id && !guard.contractor_id && can('hr.create')"
                    :action="`/training/guards/${guard.id}/employee`"
                    method="post"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        <UserPlus class="me-2 size-4" />
                        {{ t('Hire as employee') }}
                    </Button>
                </Form>
                <Form
                    v-if="(isCompleted || isCertified) && !guard.employee_id && !guard.contractor_id && can('hr.create')"
                    :action="`/training/guards/${guard.id}/contractor`"
                    method="post"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing" variant="outline">
                        <UserPlus class="me-2 size-4" />
                        {{ t('Hire as contractor') }}
                    </Button>
                </Form>
                <Button
                    v-else-if="guard.employee_id"
                    variant="outline"
                    as-child
                >
                    <Link :href="`/hr/employees/${guard.employee_id}`">
                        {{ t('View employee') }}
                    </Link>
                </Button>
                <Button
                    v-else-if="guard.contractor_id"
                    variant="outline"
                    as-child
                >
                    <Link :href="`/hr/contractors/${guard.contractor_id}`">
                        {{ t('View contractor') }}
                    </Link>
                </Button>
            </template>
        </V2DetailHero>

        <nav
            class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl border border-border/70 bg-muted/20 p-4"
            aria-label="Training progress"
        >
            <template
                v-for="(step, index) in pipelineSteps"
                :key="step.key"
            >
                <div
                    class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-[120px]"
                >
                    <CheckCircle2
                        v-if="step.done && !step.active"
                        class="size-5 shrink-0 text-emerald-600"
                    />
                    <Circle
                        v-else-if="step.active"
                        class="size-5 shrink-0 text-primary"
                    />
                    <Circle
                        v-else
                        class="size-5 shrink-0 text-muted-foreground/40"
                    />
                    <span
                        :class="
                            cn(
                                'truncate text-xs font-medium sm:text-sm',
                                step.active && 'text-primary',
                                step.done && !step.active && 'text-foreground',
                                !step.done && !step.active && 'text-muted-foreground',
                            )
                        "
                    >
                        {{ step.label }}
                    </span>
                </div>
                <div
                    v-if="index < pipelineSteps.length - 1"
                    class="hidden h-px flex-1 bg-border sm:block"
                    aria-hidden="true"
                />
            </template>
        </nav>

        <MisTabs v-model="activeTab" :tabs="tabs" nowrap />

        <V2Panel v-if="activeTab === 'overview'" :title="t('Personal details')">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="detail-field">
                    <span>{{ t('Tazkira number') }}</span>
                    <strong>{{ guard.tazkira_number ?? '—' }}</strong>
                </div>
                <div class="detail-field">
                    <span>{{ t('ID card number') }}</span>
                    <strong>{{ guard.id_card_number ?? '—' }}</strong>
                </div>
                <div class="detail-field">
                    <span>{{ t('Training path') }}</span>
                    <strong>{{ pathLabel(guard.training_path) }}</strong>
                </div>
                <div class="detail-field">
                    <span>{{ t('Start date') }}</span>
                    <strong>{{ formatDate(guard.start_date) }}</strong>
                </div>
                <div class="detail-field">
                    <span>{{ t('End date') }}</span>
                    <strong>{{ formatDate(guard.end_date) }}</strong>
                </div>
                <div v-if="guard.created_by" class="detail-field">
                    <span>{{ t('Recorded by') }}</span>
                    <strong>{{ guard.created_by.name }}</strong>
                </div>
            </div>
            <p
                v-if="guard.notes"
                class="mt-4 rounded-xl border border-border/70 bg-muted/15 p-4 text-sm whitespace-pre-wrap"
            >
                {{ guard.notes }}
            </p>
        </V2Panel>

        <div v-else-if="activeTab === 'training'" class="grid gap-6">
            <template v-if="isRegistered">
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'After registration, send this guard to the Public Protection Deputy or train them in the company.',
                        )
                    }}
                </p>
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card class="flex flex-col border-primary/20">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Shield class="size-5 text-primary" />
                                {{ t('Public Protection Deputy') }}
                            </CardTitle>
                            <CardDescription>
                                {{
                                    t(
                                        'Send registered guards to the Public Protection Deputy for a specific period. Each guard has a fee number; the company pays the total.',
                                    )
                                }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="mt-auto">
                            <Button as-child class="w-full sm:w-auto">
                                <Link
                                    :href="`/training/payments/create?guard_ids=${guard.id}&batch=${encodeURIComponent(guard.batch_number)}`"
                                >
                                    {{ t('Send to Public Protection Deputy') }}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Can permission="training.edit">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <Building2 class="size-5 text-primary" />
                                    {{ t('Company training') }}
                                </CardTitle>
                                <CardDescription>
                                    {{ t('Assign to company training') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    :action="`/training/guards/${guard.id}/company`"
                                    method="post"
                                    class="grid gap-4"
                                    v-slot="{ errors, processing }"
                                >
                                    <div class="v2-field">
                                        <Label for="company_trainer">{{
                                            t('Trainer')
                                        }}</Label>
                                        <Input
                                            id="company_trainer"
                                            name="company_trainer"
                                        />
                                        <InputError
                                            :message="errors.company_trainer"
                                        />
                                    </div>
                                    <div class="v2-field">
                                        <Label for="company_location">{{
                                            t('Location')
                                        }}</Label>
                                        <Input
                                            id="company_location"
                                            name="company_location"
                                        />
                                        <InputError
                                            :message="errors.company_location"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        :disabled="processing"
                                    >
                                        <GraduationCap class="me-2 size-4" />
                                        {{ t('Assign to company training') }}
                                    </Button>
                                </Form>
                            </CardContent>
                        </Card>
                    </Can>
                </div>
            </template>

            <V2Panel v-else :title="t('Training assignment')">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="detail-field">
                        <span>{{ t('Training path') }}</span>
                        <strong>{{ pathLabel(guard.training_path) }}</strong>
                    </div>

                    <template v-if="guard.training_path === 'ministry'">
                        <div class="detail-field">
                            <span>{{ t('Fee amount') }}</span>
                            <strong>{{
                                formatAfn(Number(guard.fee_amount ?? 0))
                            }}</strong>
                        </div>
                        <div class="detail-field sm:col-span-2 lg:col-span-1">
                            <span>{{ t('Ministry period') }}</span>
                            <strong>
                                {{ formatDate(guard.ministry_period_start) }}
                                –
                                {{ formatDate(guard.ministry_period_end) }}
                            </strong>
                        </div>
                        <div
                            v-if="guard.ministry_payment"
                            class="detail-field"
                        >
                            <span>{{ t('Ministry payment') }}</span>
                            <Link
                                :href="`/training/payments/${guard.ministry_payment.id}`"
                                class="font-semibold text-primary hover:underline"
                            >
                                {{ guard.ministry_payment.reference_number }}
                            </Link>
                        </div>
                    </template>

                    <template v-else-if="guard.training_path === 'company'">
                        <div class="detail-field">
                            <span>{{ t('Trainer') }}</span>
                            <strong>{{ guard.company_trainer ?? '—' }}</strong>
                        </div>
                        <div class="detail-field">
                            <span>{{ t('Location') }}</span>
                            <strong>{{
                                guard.company_location ?? '—'
                            }}</strong>
                        </div>
                    </template>
                </div>

                <Form
                    v-if="inTraining && can('training.edit')"
                    :action="`/training/guards/${guard.id}/complete`"
                    method="post"
                    class="mt-6 flex flex-wrap gap-3 border-t border-border/70 pt-6"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        <CheckCircle2 class="me-2 size-4" />
                        {{ t('Mark training completed') }}
                    </Button>
                </Form>

                <p
                    v-else-if="isCompleted || isCertified"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    {{ t('Training marked as completed.') }}
                </p>
            </V2Panel>
        </div>

        <Card v-else-if="activeTab === 'certificate'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Award class="size-5 text-primary" />
                    {{ t('Certificate of completion') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="isCertified"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <div class="detail-field">
                        <span>{{ t('Certificate number') }}</span>
                        <strong>{{ guard.certificate_number }}</strong>
                    </div>
                    <div class="detail-field">
                        <span>{{ t('Issue date') }}</span>
                        <strong>{{
                            formatDate(guard.certificate_issued_at)
                        }}</strong>
                    </div>
                    <div v-if="guard.certificate_url" class="sm:col-span-2">
                        <FileLink
                            :href="guard.certificate_url"
                            :label="
                                guard.certificate_original_filename ??
                                t('Scanned certificate')
                            "
                            show-icon
                        />
                    </div>
                    <Button variant="outline" as-child class="sm:col-span-2 w-fit">
                        <Link
                            :href="`/training/guards/${guard.id}/certificate/print`"
                            target="_blank"
                        >
                            <Printer class="me-2 size-4" />
                            {{ t('Print certificate') }}
                        </Link>
                    </Button>
                </div>

                <div
                    v-else-if="(isCompleted || isCertified) && (guard.employee || guard.contractor)"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <div class="detail-field">
                        <span>{{ t('HR status') }}</span>
                        <strong>
                            <Link
                                v-if="guard.employee"
                                :href="`/hr/employees/${guard.employee_id}`"
                                class="text-primary"
                            >
                                {{ t('Employee') }}
                            </Link>
                            <Link
                                v-else-if="guard.contractor"
                                :href="`/hr/contractors/${guard.contractor_id}`"
                                class="text-primary"
                            >
                                {{ t('Contractor') }}
                            </Link>
                        </strong>
                    </div>
                </div>

                <Form
                    v-else-if="canCertify && can('training.edit')"
                    :action="`/training/guards/${guard.id}/certificate`"
                    method="post"
                    class="grid max-w-md gap-4"
                    :options="{ forceFormData: true }"
                    v-slot="{ errors, processing }"
                >
                    <div class="v2-field">
                        <Label for="certificate_number">{{
                            t('Certificate number')
                        }}</Label>
                        <Input
                            id="certificate_number"
                            name="certificate_number"
                            :default-value="next_certificate_number"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{
                                t(
                                    'Auto-generated. You can change this before saving.',
                                )
                            }}
                        </p>
                        <InputError :message="errors.certificate_number" />
                    </div>
                    <div class="v2-field">
                        <Label for="certificate_issued_at">{{
                            t('Issue date')
                        }}</Label>
                        <Input
                            id="certificate_issued_at"
                            name="certificate_issued_at"
                            type="date"
                        />
                        <InputError :message="errors.certificate_issued_at" />
                    </div>
                    <OptionalAttachmentField
                        name="certificate"
                        :label="t('Scanned certificate')"
                        :error="errors.certificate"
                    />
                    <Button type="submit" :disabled="processing">
                        {{ t('Issue certificate') }}
                    </Button>
                </Form>

                <p v-else class="text-sm text-muted-foreground">
                    {{
                        inTraining
                            ? t('Mark training completed')
                            : t('Not assigned')
                    }}
                </p>
            </CardContent>
        </Card>

        <StatusChangeHistory
            v-else
            :logs="guard.status_change_logs ?? []"
            :title="t('Activation log')"
        />
    </V2ListPage>
</template>

<style scoped>
.detail-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    border: 1px solid color-mix(in srgb, var(--border) 70%, transparent);
    background: color-mix(in srgb, var(--muted) 15%, transparent);
}

.detail-field span {
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted-foreground);
}

.detail-field strong {
    font-size: 0.875rem;
    font-weight: 600;
}
</style>
