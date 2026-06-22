<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
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

const { t, viewAction, editAction } = useMisPage();

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
    editAction(`/hr/employees/${employee.id}/edit`),
    ...personnelStatusActions({
        url: `/hr/employees/${employee.id}`,
        name: fullName(employee),
        status: employee.status,
        t,
    }),
];
</script>

<template>
    <Head :title="t('Employees')" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="t('Employees')"
                :description="t('Manage staff records and employment details')"
            />
            <Button as-child>
                <Link href="/hr/employees/create">
                    <Plus class="me-1 size-4" />
                    {{ t('Add Employee') }}
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="size-5" />
                    {{ t('All Employees') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(':count employees', {
                            count: String(
                                employees.meta?.total ?? employees.data.length,
                            ),
                        })
                    }}
                </CardDescription>
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
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
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
