<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Users } from '@lucide/vue';
import MisCreateButton from '@/components/MisCreateButton.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import type { RowActionItem } from '@/lib/row-actions';
import { personnelStatusActions } from '@/lib/status-actions';

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string | null;
    email?: string | null;
    status: string;
    is_permanent?: boolean;
    job_detail?: {
        designation?: string | null;
        department?: { name: string } | null;
    } | null;
}

interface PaginatedEmployees {
    data: Employee[];
    meta?: { total: number };
}

interface Props {
    employees: PaginatedEmployees;
    filters?: { search?: string; status?: string };
}

defineProps<Props>();

const { t, viewAction, editAction, gateActions } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Employees', href: '/hr/employees' },
        ],
    },
});

const fullName = (employee: Employee): string =>
    `${employee.first_name} ${employee.last_name}`;

const employeeActions = (employee: Employee): RowActionItem[] => [
    viewAction(`/hr/employees/${employee.id}`),
    editAction(`/hr/employees/${employee.id}/edit`, 'hr.edit'),
    ...gateActions(
        personnelStatusActions({
            url: `/hr/employees/${employee.id}`,
            name: fullName(employee),
            status: employee.status,
            t,
        }),
        'hr.edit',
    ),
];
</script>

<template>
    <Head :title="t('Employees')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="size-5" />
                    {{ t('All Employees') }}
                </CardTitle>
                <CardAction>
                    <MisCreateButton href="/hr/employees/create" permission="hr.create">
                        {{ t('Add Employee') }}
                    </MisCreateButton>
                </CardAction>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/hr/employees" class="relative max-w-sm">
                    <Search
                        class="absolute top-1/2 start-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        :placeholder="t('Search employees...')"
                        class="ps-9"
                    />
                </form>

                <div
                    v-if="employees.data.length === 0"
                    class="ui-empty-state"
                >
                    {{ t('No employees found.') }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-start text-muted-foreground">
                                <th class="pb-3 pe-4 font-medium">{{ t('Name') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Designation')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{
                                    t('Department')
                                }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Contact') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Type') }}</th>
                                <th class="pb-3 pe-4 font-medium">{{ t('Status') }}</th>
                                <th class="pb-3 text-end font-medium">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="employee in employees.data"
                                :key="employee.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pe-4">
                                    <Link
                                        :href="`/hr/employees/${employee.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ fullName(employee) }}
                                    </Link>
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{ employee.job_detail?.designation ?? '—' }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    {{
                                        employee.job_detail?.department?.name ??
                                        '—'
                                    }}
                                </td>
                                <td class="py-3 pe-4 text-muted-foreground">
                                    <div>{{ employee.phone ?? '—' }}</div>
                                    <div class="text-xs">{{ employee.email ?? '' }}</div>
                                </td>
                                <td class="py-3 pe-4">
                                    <Badge :variant="employee.is_permanent ? 'default' : 'outline'">
                                        {{
                                            employee.is_permanent
                                                ? t('Permanent')
                                                : t('Project-based')
                                        }}
                                    </Badge>
                                </td>
                                <td class="py-3 pe-4">
                                    <Badge
                                        :variant="
                                            employee.status === 'active'
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{
                                            employee.status === 'active'
                                                ? t('Active')
                                                : employee.status === 'inactive'
                                                  ? t('Inactive')
                                                  : employee.status
                                        }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-end">
                                    <RowActionsMenu
                                        :actions="employeeActions(employee)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
