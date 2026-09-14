<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Paperclip, Plus, Users } from '@lucide/vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import OptionalAttachmentField from '@/components/OptionalAttachmentField.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import SortableTh from '@/components/SortableTh.vue';
import {
    V2Hero,
    V2ListPage,
    V2Pager,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { formatDate, type Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface RecipientRow {
    id: number;
    status: string;
    user?: { id: number; name: string } | null;
}

interface AssignmentRow {
    id: number;
    description: string;
    assigned_on: string | null;
    reply_by: string | null;
    status: string;
    created_at: string;
    created_by?: { id: number; name: string } | null;
    recipients?: RecipientRow[];
    recipients_count?: number;
    replies_count?: number;
    attachments?: Array<{ id: number }>;
}

const props = defineProps<{
    assignments: Paginated<AssignmentRow>;
    users: UserOption[];
    canAssign: boolean;
}>();

const { t, viewAction, deleteAction } = useMisPage();

const onlyKeys = ['assignments', 'users', 'canAssign'];

const showForm = ref(false);
const assignTo = ref<'all' | 'users'>('users');
const selectedUserIds = ref<number[]>([]);

const { sortedRows } = provideTableSort(() => props.assignments.data, {
    accessors: {
        detail: (row) => row.description,
        assigned_on: (row) => row.assigned_on,
        reply_by: (row) => row.reply_by,
        status: (row) => row.status,
        assignees: (row) => row.recipients_count ?? row.recipients?.length ?? 0,
        replies: (row) => row.replies_count ?? 0,
        created_by: (row) => row.created_by?.name,
    },
});

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'detail', label: t('Detail') },
    { key: 'assigned_on', label: t('Assigned on') },
    { key: 'reply_by', label: t('Reply by') },
    { key: 'status', label: t('Status') },
    { key: 'assignees', label: t('Assignees') },
    { key: 'replies', label: t('Replies') },
    { key: 'created_by', label: t('Created by') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Assignments', href: '/assignments' }],
    },
});

const truncate = (text: string, max = 80): string =>
    text.length <= max ? text : `${text.slice(0, max).trim()}…`;

const statusVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    if (status === 'completed') {
        return 'secondary';
    }

    if (status === 'cancelled') {
        return 'destructive';
    }

    return 'outline';
};

const isReplyOverdue = (row: AssignmentRow): boolean => {
    if (row.status !== 'pending' || !row.reply_by) {
        return false;
    }

    return new Date(row.reply_by) < new Date(new Date().toDateString());
};

const selectedCount = computed(() => selectedUserIds.value.length);

const assignmentActions = (row: AssignmentRow): RowActionItem[] => [
    viewAction(`/assignments/${row.id}`),
    deleteAction(
        {
            href: `/assignments/${row.id}`,
            title: t('Delete assignment'),
            description: t('Delete this assignment?'),
        },
        'assignments.delete',
    ),
];
</script>

<template>
    <Head :title="t('Assignments')" />

    <V2ListPage>
        <V2Hero image="/images/gs-hero-operations.png">
            <template #eyebrow>{{ t('Work') }}</template>
            <template #title>{{ t('Assignments') }}</template>
            <template #description>
                {{
                    t(
                        'Assign tasks to one user, several users, or everyone. Assignees get a notification and can reply with attachments.',
                    )
                }}
            </template>
            <template #side>
                <Can v-if="canAssign" permission="assignments.create">
                    <button
                        type="button"
                        class="create-btn"
                        @click="showForm = true"
                    >
                        <Plus />
                        {{ t('New assignment') }}
                    </button>
                </Can>
            </template>
        </V2Hero>

        <V2TablePanel table-id="mis-assignments" :columns="tableColumns">
            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="detail">{{ t('Detail') }}</SortableTh>
                            <SortableTh column="assigned_on">{{ t('Assigned on') }}</SortableTh>
                            <SortableTh column="reply_by">{{ t('Reply by') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="assignees">{{ t('Assignees') }}</SortableTh>
                            <SortableTh column="replies">{{ t('Replies') }}</SortableTh>
                            <SortableTh column="created_by">{{ t('Created by') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in sortedRows"
                            :key="row.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="assignments.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/assignments/${row.id}`"
                                    class="font-medium hover:underline"
                                >
                                    {{ truncate(row.description) }}
                                </Link>
                                <Paperclip
                                    v-if="row.attachments?.length"
                                    class="ms-2 inline size-3.5 text-muted-foreground"
                                />
                            </td>
                            <td class="muted">
                                {{ row.assigned_on ? formatDate(row.assigned_on) : '—' }}
                            </td>
                            <td
                                :class="{
                                    'text-destructive font-medium': isReplyOverdue(row),
                                }"
                            >
                                {{ row.reply_by ? formatDate(row.reply_by) : '—' }}
                            </td>
                            <td>
                                <Badge :variant="statusVariant(row.status)">
                                    {{ row.status }}
                                </Badge>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1">
                                    <Users class="size-3.5 text-muted-foreground" />
                                    {{
                                        row.recipients_count ??
                                        row.recipients?.length ??
                                        0
                                    }}
                                </span>
                            </td>
                            <td class="nums">{{ row.replies_count ?? 0 }}</td>
                            <td class="muted">
                                {{ row.created_by?.name ?? '—' }}
                            </td>
                            <td class="end">
                                <RowActionsMenu
                                    v-if="assignmentActions(row).length"
                                    :actions="assignmentActions(row)"
                                />
                                <span v-else class="muted text-xs">—</span>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!assignments.data.length"
                            :colspan="visibleColCount"
                            :title="t('No assignments yet')"
                            :description="
                                t(
                                    'Create an assignment to notify team members.',
                                )
                            "
                        />
                    </tbody>
                </table>
            </template>

            <template v-if="assignments.links?.length" #pager>
                <V2Pager :items="assignments" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <Dialog v-model:open="showForm">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('New assignment') }}</DialogTitle>
                </DialogHeader>

                <Form
                    action="/assignments"
                    method="post"
                    enctype="multipart/form-data"
                    class="grid gap-4"
                    @success="showForm = false"
                >
                    <div class="grid gap-2">
                        <Label for="description">{{ t('Description') }}</Label>
                        <Textarea
                            id="description"
                            name="description"
                            rows="4"
                            required
                            :placeholder="t('What should be done?')"
                        />
                        <InputError name="description" />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="assigned_on">{{ t('Assigned on') }}</Label>
                            <Input
                                id="assigned_on"
                                name="assigned_on"
                                type="date"
                            />
                            <InputError name="assigned_on" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="reply_by">{{ t('Reply by') }}</Label>
                            <Input id="reply_by" name="reply_by" type="date" />
                            <InputError name="reply_by" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">{{ t('Status') }}</Label>
                        <select
                            id="status"
                            name="status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="pending">{{ t('Pending') }}</option>
                            <option value="completed">{{ t('Completed') }}</option>
                            <option value="cancelled">{{ t('Cancelled') }}</option>
                        </select>
                    </div>

                    <OptionalAttachmentField />

                    <div class="grid gap-2 rounded-lg border p-3">
                        <Label>{{ t('Assign to') }}</Label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                v-model="assignTo"
                                type="radio"
                                name="assign_to"
                                value="all"
                                class="size-4"
                            />
                            {{ t('All active users') }}
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                v-model="assignTo"
                                type="radio"
                                name="assign_to"
                                value="users"
                                class="size-4"
                            />
                            {{ t('Selected users') }}
                            <span
                                v-if="assignTo === 'users' && selectedCount"
                                class="text-muted-foreground"
                            >
                                ({{ selectedCount }})
                            </span>
                        </label>

                        <div
                            v-if="assignTo === 'users'"
                            class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-md border p-2"
                        >
                            <label
                                v-for="user in users"
                                :key="user.id"
                                class="flex cursor-pointer items-start gap-2 text-sm"
                            >
                                <input
                                    v-model="selectedUserIds"
                                    type="checkbox"
                                    class="mt-0.5 size-4"
                                    name="user_ids[]"
                                    :value="user.id"
                                />
                                <span>
                                    <span class="font-medium">{{ user.name }}</span>
                                    <span class="block text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </span>
                                </span>
                            </label>
                            <p
                                v-if="users.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('No users available.') }}
                            </p>
                        </div>

                        <InputError name="user_ids" />
                        <InputError name="user_ids.0" />
                        <InputError name="assign_to" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showForm = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit">{{ t('Create assignment') }}</Button>
                    </div>
                </Form>
            </DialogContent>
        </Dialog>
    </V2ListPage>
</template>
