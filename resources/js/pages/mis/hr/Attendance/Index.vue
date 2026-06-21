<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays } from '@lucide/vue';
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
import { Label } from '@/components/ui/label';
import type { RowActionItem } from '@/lib/row-actions';
import { attendanceStatusActions } from '@/lib/status-actions';

interface AttendanceRecord {
    id: number;
    personnel_type: string;
    personnel_id: number;
    personnel_name?: string | null;
    project?: { id: number; code: string; name: string } | null;
    year: number;
    month: number;
    days_present: number;
    days_absent: number;
    days_leave: number;
    overtime_hours?: number | null;
    status: string;
}

interface Props {
    attendances: AttendanceRecord[];
    filters?: {
        year?: number;
        month?: number;
        project_id?: number;
    };
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'HR', href: '/hr/employees' },
            { title: 'Attendance', href: '/hr/attendance' },
        ],
    },
});

const monthName = (month: number): string =>
    new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(2000, month - 1, 1),
    );

const attendanceActions = (record: AttendanceRecord): RowActionItem[] =>
    attendanceStatusActions(record.id, record.status);
</script>

<template>
    <Head title="Attendance" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Attendance"
            description="Monthly attendance records for employees and contractors"
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CalendarDays class="size-5" />
                    Attendance Records
                </CardTitle>
                <CardDescription>
                    Filter by period and project
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/hr/attendance"
                    class="flex flex-wrap items-end gap-4"
                >
                    <div class="grid gap-2">
                        <Label for="year">Year</Label>
                        <Input
                            id="year"
                            name="year"
                            type="number"
                            :default-value="filters?.year ?? new Date().getFullYear()"
                            class="w-28"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="month">Month</Label>
                        <Input
                            id="month"
                            name="month"
                            type="number"
                            min="1"
                            max="12"
                            :default-value="filters?.month ?? new Date().getMonth() + 1"
                            class="w-20"
                        />
                    </div>
                    <Button type="submit">Filter</Button>
                </form>

                <div
                    v-if="attendances.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No attendance records for this period.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Personnel</th>
                                <th class="pb-3 pr-4 font-medium">Type</th>
                                <th class="pb-3 pr-4 font-medium">Project</th>
                                <th class="pb-3 pr-4 font-medium">Period</th>
                                <th class="pb-3 pr-4 font-medium">Present</th>
                                <th class="pb-3 pr-4 font-medium">Absent</th>
                                <th class="pb-3 pr-4 font-medium">Leave</th>
                                <th class="pb-3 pr-4 font-medium">OT Hours</th>
                                <th class="pb-3 pr-4 font-medium">Status</th>
                                <th class="pb-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="record in attendances"
                                :key="record.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4 font-medium">
                                    {{
                                        record.personnel_name ??
                                        `#${record.personnel_id}`
                                    }}
                                </td>
                                <td class="py-3 pr-4 capitalize text-muted-foreground">
                                    {{ record.personnel_type }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ record.project?.code ?? '—' }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ monthName(record.month) }} {{ record.year }}
                                </td>
                                <td class="py-3 pr-4">{{ record.days_present }}</td>
                                <td class="py-3 pr-4">{{ record.days_absent }}</td>
                                <td class="py-3 pr-4">{{ record.days_leave }}</td>
                                <td class="py-3 pr-4">
                                    {{ record.overtime_hours ?? 0 }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="outline">{{ record.status }}</Badge>
                                </td>
                                <td class="py-3 text-right">
                                    <RowActionsMenu
                                        :actions="attendanceActions(record)"
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
