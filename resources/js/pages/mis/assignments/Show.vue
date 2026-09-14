<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Calendar, MessageSquare, Users } from '@lucide/vue';
import Can from '@/components/Can.vue';
import EntityAttachments from '@/components/EntityAttachments.vue';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import { V2DetailHero, V2ListPage } from '@/components/v2';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMisPage } from '@/composables/useMisPage';
import { formatDate } from '@/lib/format';

interface AttachmentRow {
    id: number;
    title: string | null;
    original_filename: string;
    file_size: number | null;
    download_url: string;
    created_at: string;
}

interface RecipientRow {
    id: number;
    status: string;
    user?: { id: number; name: string } | null;
}

interface ReplyRow {
    id: number;
    description: string;
    created_at: string;
    user?: { id: number; name: string } | null;
    attachments?: AttachmentRow[];
}

interface AssignmentDetail {
    id: number;
    description: string;
    assigned_on: string | null;
    reply_by: string | null;
    status: string;
    created_at: string;
    created_by?: { id: number; name: string } | null;
    recipients?: RecipientRow[];
    replies?: ReplyRow[];
    attachments?: AttachmentRow[];
}

const props = defineProps<{
    assignment: AssignmentDetail;
    canManage: boolean;
    isRecipient: boolean;
    myRecipientStatus?: string | null;
}>();

const { t } = useMisPage();

const deleteAssignment = (): void => {
    if (!confirm(t('Delete this assignment?'))) {
        return;
    }

    router.delete(`/assignments/${props.assignment.id}`);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Assignments', href: '/assignments' },
            { title: 'Detail', href: '#' },
        ],
    },
});

const isReplyOverdue = computed(() => {
    if (props.assignment.status !== 'pending' || !props.assignment.reply_by) {
        return false;
    }

    return (
        new Date(props.assignment.reply_by) <
        new Date(new Date().toDateString())
    );
});

const recipientStatusOptions = [
    { value: 'pending', label: t('Pending') },
    { value: 'submitted', label: t('Submitted') },
    { value: 'completed', label: t('Completed') },
];

const formatDateTime = (value: string): string =>
    new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>

<template>
    <Head :title="t('Assignment')" />

    <V2ListPage>
        <V2DetailHero image="/images/gs-hero-operations.png">
            <template #eyebrow>
                <Link href="/assignments" class="hover:underline">
                    {{ t('Assignments') }}
                </Link>
            </template>
            <template #title>{{ t('Assignment') }}</template>
            <template #description>
                <span class="inline-flex flex-wrap items-center gap-2">
                    <Badge variant="outline">{{ assignment.status }}</Badge>
                    <span
                        v-if="assignment.assigned_on"
                        class="inline-flex items-center gap-1 text-sm"
                    >
                        <Calendar class="size-3.5" />
                        {{ t('Assigned on') }}:
                        {{ formatDate(assignment.assigned_on) }}
                    </span>
                    <span
                        v-if="assignment.reply_by"
                        class="inline-flex items-center gap-1 text-sm"
                        :class="{ 'text-destructive': isReplyOverdue }"
                    >
                        <Calendar class="size-3.5" />
                        {{ t('Reply by') }}:
                        {{ formatDate(assignment.reply_by) }}
                    </span>
                    <span v-if="isReplyOverdue" class="text-sm text-destructive">
                        ({{ t('Overdue') }})
                    </span>
                </span>
            </template>
        </V2DetailHero>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Description') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-wrap text-sm">
                            {{ assignment.description }}
                        </p>
                        <p
                            v-if="assignment.created_by"
                            class="mt-4 text-xs text-muted-foreground"
                        >
                            {{ t('Created by') }} {{ assignment.created_by.name }} ·
                            {{ formatDateTime(assignment.created_at) }}
                        </p>
                    </CardContent>
                </Card>

                <EntityAttachments
                    v-if="assignment.attachments?.length"
                    :attachments="assignment.attachments"
                />

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <MessageSquare class="size-4" />
                            {{ t('Replies') }}
                            ({{ assignment.replies?.length ?? 0 }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="!assignment.replies?.length"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('No replies yet.') }}
                        </div>
                        <article
                            v-for="reply in assignment.replies"
                            :key="reply.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-medium">
                                    {{ reply.user?.name ?? t('User') }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDateTime(reply.created_at) }}
                                </span>
                            </div>
                            <p class="whitespace-pre-wrap text-sm">
                                {{ reply.description }}
                            </p>
                            <EntityAttachments
                                v-if="reply.attachments?.length"
                                class="mt-3 border-0 shadow-none"
                                :attachments="reply.attachments"
                            />
                        </article>

                        <div
                            v-if="isRecipient"
                            class="rounded-lg border border-dashed p-4"
                        >
                            <p class="mb-3 text-sm text-muted-foreground">
                                {{ t('Your status') }}:
                                <Badge variant="outline" class="ms-1">
                                    {{ myRecipientStatus ?? 'pending' }}
                                </Badge>
                            </p>
                            <Form
                                :action="`/assignments/${assignment.id}/replies`"
                                method="post"
                                enctype="multipart/form-data"
                                class="grid gap-3"
                                preserve-scroll
                            >
                                <div class="grid gap-2">
                                    <Label for="reply_description">
                                        {{ t('Your reply') }}
                                    </Label>
                                    <Textarea
                                        id="reply_description"
                                        name="description"
                                        rows="3"
                                        required
                                        :placeholder="t('Write your update or response…')"
                                    />
                                    <InputError name="description" />
                                </div>
                                <OptionalAttachmentField />
                                <Button type="submit" size="sm">
                                    {{ t('Submit reply') }}
                                </Button>
                            </Form>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Users class="size-4" />
                            {{ t('Assignees') }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y text-sm">
                            <li
                                v-for="recipient in assignment.recipients"
                                :key="recipient.id"
                                class="flex items-center justify-between py-2"
                            >
                                <span>{{ recipient.user?.name ?? '—' }}</span>
                                <Badge variant="outline">{{ recipient.status }}</Badge>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Can v-if="canManage" permission="assignments.edit">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">
                                {{ t('Update assignment') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                :action="`/assignments/${assignment.id}`"
                                method="put"
                                class="grid gap-3"
                                preserve-scroll
                            >
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="edit_assigned_on">{{
                                            t('Assigned on')
                                        }}</Label>
                                        <Input
                                            id="edit_assigned_on"
                                            name="assigned_on"
                                            type="date"
                                            :value="
                                                assignment.assigned_on?.slice(0, 10) ??
                                                ''
                                            "
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="edit_reply_by">{{
                                            t('Reply by')
                                        }}</Label>
                                        <Input
                                            id="edit_reply_by"
                                            name="reply_by"
                                            type="date"
                                            :value="
                                                assignment.reply_by?.slice(0, 10) ??
                                                ''
                                            "
                                        />
                                    </div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit_status">{{ t('Status') }}</Label>
                                    <select
                                        id="edit_status"
                                        name="status"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option
                                            value="pending"
                                            :selected="assignment.status === 'pending'"
                                        >
                                            {{ t('Pending') }}
                                        </option>
                                        <option
                                            value="completed"
                                            :selected="assignment.status === 'completed'"
                                        >
                                            {{ t('Completed') }}
                                        </option>
                                        <option
                                            value="cancelled"
                                            :selected="assignment.status === 'cancelled'"
                                        >
                                            {{ t('Cancelled') }}
                                        </option>
                                    </select>
                                </div>
                                <Button type="submit" size="sm" variant="secondary">
                                    {{ t('Save changes') }}
                                </Button>
                            </Form>
                        </CardContent>
                    </Card>
                </Can>

                <Can v-if="canManage" permission="assignments.edit">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">
                                {{ t('Assignee progress') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                :action="`/assignments/${assignment.id}`"
                                method="put"
                                class="space-y-3"
                                preserve-scroll
                            >
                                <div
                                    v-for="(recipient, index) in assignment.recipients"
                                    :key="recipient.id"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        type="hidden"
                                        :name="`recipient_statuses[${index}][id]`"
                                        :value="recipient.id"
                                    />
                                    <span class="min-w-0 flex-1 truncate text-sm">
                                        {{ recipient.user?.name }}
                                    </span>
                                    <select
                                        :name="`recipient_statuses[${index}][status]`"
                                        class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                                    >
                                        <option
                                            v-for="opt in recipientStatusOptions"
                                            :key="opt.value"
                                            :value="opt.value"
                                            :selected="recipient.status === opt.value"
                                        >
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>
                                <Button type="submit" size="sm">
                                    {{ t('Update progress') }}
                                </Button>
                            </Form>
                        </CardContent>
                    </Card>
                </Can>

                <Can permission="assignments.delete">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="w-full"
                        @click="deleteAssignment"
                    >
                        {{ t('Delete assignment') }}
                    </Button>
                </Can>
            </div>
        </div>
    </V2ListPage>
</template>
