<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Users } from '@lucide/vue';
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
    {
        label: 'View',
        icon: Eye,
        href: `/hr/employees/${employee.id}`,
    },
    {
        label: 'Edit',
        icon: Pencil,
        href: `/hr/employees/${employee.id}/edit`,
    },
    ...personnelStatusActions({
        url: `/hr/employees/${employee.id}`,
        name: fullName(employee),
        status: employee.status,
    }),
];
</script>

<template>
    <Head title="Employees" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title="Employees"
                description="Manage staff records and employment details"
            />
            <Button as-child>
                <Link href="/hr/employees/create">
                    <Plus class="size-4" />
                    Add Employee
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="size-5" />
                    All Employees
                </CardTitle>
                <CardDescription>
                    {{ employees.meta?.total ?? employees.data.length }} employees
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form method="get" action="/hr/employees" class="relative max-w-sm">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        name="search"
                        :default-value="filters?.search"
                        placeholder="Search employees..."
                        class="pl-9"
                    />
                </form>

                <div
                    v-if="employees.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No employees found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Name</th>
                                <th class="pb-3 pr-4 font-medium">Designation</th>
                                <th class="pb-3 pr-4 font-medium">Department</th>
                                <th class="pb-3 pr-4 font-medium">Contact</th>
                                <th class="pb-3 pr-4 font-medium">Status</th>
                                <th class="pb-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="employee in employees.data"
                                :key="employee.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">
                                    <Link
                                        :href="`/hr/employees/${employee.id}`"
                                        class="font-medium hover:underline"
                                    >
                                        {{ fullName(employee) }}
                                    </Link>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ employee.job_detail?.designation ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{
                                        employee.job_detail?.department?.name ??
                                        '—'
                                    }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    <div>{{ employee.phone ?? '—' }}</div>
                                    <div class="text-xs">{{ employee.email ?? '' }}</div>
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge
                                        :variant="
                                            employee.status === 'active'
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ employee.status }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-right">
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
