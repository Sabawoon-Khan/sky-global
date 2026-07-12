<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { ArrowLeft, Printer } from '@lucide/vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import { useMisPage } from '@/composables/useMisPage';

interface CalendarDay {
    day: number;
    weekday: string;
}

interface StaffRow {
    id: number;
    name: string;
    designation: string;
    joining_date: string;
    days_present: number;
    days_absent: number;
    days_sick_leave: number;
    days_annual_leave: number;
    days_casual_leave: number;
    days_other: number;
    daily_marks: Record<string, string>;
    total: number;
    remarks: string;
}

interface ProjectOption {
    id: number;
    code: string;
    name: string;
    location?: string | null;
}

interface Props {
    staff: StaffRow[];
    calendar_days: CalendarDay[];
    project?: ProjectOption | null;
    sheet_title: string;
    location?: string | null;
    ssm_name?: string | null;
    filters?: {
        date_from?: string;
        date_to?: string;
        project_id?: number;
        sheet_id?: number;
    };
    period_label: string;
}

const props = defineProps<Props>();

const { t } = useMisPage();

const backHref = (): string => {
    if (props.filters?.sheet_id) {
        return `/hr/attendance/create?sheet_id=${props.filters.sheet_id}`;
    }

    return '/hr/attendance';
};

const printPage = (): void => {
    window.print();
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="sheet_title" />

    <div class="sheet-page">
        <div class="sheet-toolbar">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="backHref()">
                    <ArrowLeft class="size-4" />
                    {{ t('Back to attendance') }}
                </Link>
            </Button>
            <div class="flex items-center gap-2">
                <span class="hidden text-sm text-muted-foreground sm:inline">
                    {{ period_label }}
                </span>
                <Button type="button" variant="outline" @click="printPage">
                    <Printer class="size-4" />
                    {{ t('Print') }}
                </Button>
            </div>
        </div>

        <article class="sheet-document">
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo sheet-logo--left" />
                <div class="sheet-header-center">
                    <h1 class="sheet-title">{{ sheet_title }}</h1>
                </div>
                <AppLogoImage class="sheet-logo sheet-logo--right" />
            </header>

            <div class="sheet-meta">
                <p>
                    <strong>{{ t('Period') }}:</strong>
                    {{ period_label }}
                </p>
                <p>
                    <strong>{{ t('Project Name') }}:</strong>
                    {{
                        project
                            ? `${project.name} ${project.code}`
                            : '—'
                    }}
                </p>
                <p>
                    <strong>{{ t('S.S.M') }}:</strong>
                    {{ ssm_name ?? '—' }}
                </p>
                <p>
                    <strong>{{ t('Location') }}:</strong>
                    {{ location ?? '—' }}
                </p>
            </div>

            <div class="sheet-table-wrap">
                <p
                    v-if="staff.length === 0"
                    class="sheet-empty"
                >
                    {{ t('No staff recorded on this attendance sheet yet.') }}
                </p>
                <table v-else class="sheet-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-no">NO</th>
                            <th rowspan="2" class="col-name">{{ t('Name') }}</th>
                            <th rowspan="2" class="col-designation">
                                {{ t('Designation') }}
                            </th>
                            <th rowspan="2" class="col-join">
                                {{ t('Joining Date') }}
                            </th>
                            <th
                                v-for="day in calendar_days"
                                :key="`wd-${day.day}`"
                                class="col-day"
                            >
                                {{ day.weekday }}
                            </th>
                            <th rowspan="2" class="col-sum col-absent">
                                {{ t('Absent') }}
                            </th>
                            <th rowspan="2" class="col-sum col-present">
                                {{ t('Present') }}
                            </th>
                            <th rowspan="2" class="col-sum col-sick">
                                {{ t('Sick Leave') }}
                            </th>
                            <th rowspan="2" class="col-sum col-annual">
                                {{ t('Annual Leave') }}
                            </th>
                            <th rowspan="2" class="col-sum col-casual">
                                {{ t('Casual Leave') }}
                            </th>
                            <th rowspan="2" class="col-sum col-other">
                                {{ t('Other') }}
                            </th>
                            <th rowspan="2" class="col-sum col-total">
                                {{ t('Total') }}
                            </th>
                            <th rowspan="2" class="col-remarks">
                                {{ t('Remarks') }}
                            </th>
                        </tr>
                        <tr>
                            <th
                                v-for="day in calendar_days"
                                :key="`dt-${day.day}`"
                                class="col-day"
                            >
                                {{ day.day }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(person, index) in staff"
                            :key="person.id"
                        >
                            <td class="col-no">{{ index + 1 }}</td>
                            <td class="col-name">{{ person.name }}</td>
                            <td class="col-designation">
                                {{ person.designation }}
                            </td>
                            <td class="col-join">{{ person.joining_date }}</td>
                            <td
                                v-for="day in calendar_days"
                                :key="`${person.id}-${day.day}`"
                                class="col-day"
                            >
                                {{ person.daily_marks[String(day.day)] ?? '' }}
                            </td>
                            <td class="col-sum col-absent">
                                {{ person.days_absent }}
                            </td>
                            <td class="col-sum col-present">
                                {{ person.days_present }}
                            </td>
                            <td class="col-sum col-sick">
                                {{ person.days_sick_leave }}
                            </td>
                            <td class="col-sum col-annual">
                                {{ person.days_annual_leave }}
                            </td>
                            <td class="col-sum col-casual">
                                {{ person.days_casual_leave }}
                            </td>
                            <td class="col-sum col-other">
                                {{ person.days_other }}
                            </td>
                            <td class="col-sum col-total">
                                {{ person.total }}
                            </td>
                            <td class="col-remarks">{{ person.remarks }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer class="sheet-footer">
                <div class="sheet-legend">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ t('Sick Leave') }}</th>
                                <th>{{ t('Annual Leave') }}</th>
                                <th>{{ t('Casual Leave') }}</th>
                                <th>{{ t('Other') }}</th>
                                <th>{{ t('Absent') }}</th>
                                <th>{{ t('Present') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="legend-sick" />
                                <td class="legend-annual" />
                                <td class="legend-casual" />
                                <td class="legend-other" />
                                <td class="legend-absent" />
                                <td class="legend-present" />
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="sheet-signatures">
                    <p>{{ t('Prepared By') }}: ______________________</p>
                    <p>{{ t('Checked By') }}: ______________________</p>
                </div>
            </footer>
        </article>
    </div>
</template>

<style scoped>
.sheet-page {
    min-height: 100vh;
    background: #e5e7eb;
    padding: 1rem;
}

.sheet-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    max-width: 100%;
    margin: 0 auto 0.75rem;
}

.sheet-document {
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    padding: 0.75rem 0.5rem 1rem;
}

.sheet-header {
    display: grid;
    grid-template-columns: 4rem 1fr 4rem;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.sheet-logo {
    width: 3.5rem;
    height: 3.5rem;
}

.sheet-logo--right {
    justify-self: end;
}

.sheet-header-center {
    text-align: center;
}

.sheet-title {
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.3;
    text-transform: uppercase;
    color: #111;
}

.sheet-meta {
    font-size: 0.7rem;
    line-height: 1.5;
    margin-bottom: 0.5rem;
    color: #111;
}

.sheet-meta p {
    margin: 0;
}

.sheet-empty {
    padding: 2rem 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: #6b7280;
}

.sheet-table-wrap {
    overflow-x: auto;
}

.sheet-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 0.5625rem;
    color: #111;
}

.sheet-table th,
.sheet-table td {
    border: 1px solid #111;
    padding: 0.15rem 0.1rem;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
}

.sheet-table thead th {
    background: #d9d9d9;
    font-weight: 700;
    font-size: 0.5rem;
}

.col-no {
    width: 1.4rem;
}

.col-name {
    width: 5.5rem;
    text-align: start;
    padding-inline: 0.2rem;
}

.col-designation {
    width: 3.5rem;
    font-size: 0.5rem;
}

.col-join {
    width: 3rem;
    font-size: 0.5rem;
}

.col-days-group {
    font-size: 0.55rem;
}

.col-day {
    width: 0.95rem;
    min-width: 0.95rem;
    height: 0.95rem;
    padding: 0;
    font-size: 0.45rem;
    font-weight: 600;
}

.col-sum {
    width: 1.15rem;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 0.45rem;
    padding: 0.2rem 0;
    white-space: nowrap;
}

.col-remarks {
    width: 2.5rem;
    font-size: 0.5rem;
}

.col-absent,
.legend-absent {
    background: #f4cccc !important;
}

.col-present,
.legend-present {
    background: #cfe2f3 !important;
}

.col-sick,
.legend-sick {
    background: #fff2cc !important;
}

.col-annual,
.legend-annual {
    background: #d9ead3 !important;
}

.col-casual,
.legend-casual {
    background: #fce5cd !important;
}

.col-other,
.legend-other {
    background: #d9d9d9 !important;
}

.col-total {
    background: #efefef;
    writing-mode: horizontal-tb;
    transform: none;
    font-weight: 700;
}

tbody td.col-name,
tbody td.col-designation,
tbody td.col-join,
tbody td.col-remarks {
    text-align: start;
    padding-inline: 0.15rem;
}

.sheet-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    margin-top: 0.75rem;
}

.sheet-legend table {
    border-collapse: collapse;
    font-size: 0.55rem;
}

.sheet-legend th,
.sheet-legend td {
    border: 1px solid #111;
    padding: 0.15rem 0.35rem;
    text-align: center;
    min-width: 2.5rem;
    height: 0.85rem;
}

.sheet-legend th {
    background: #d9d9d9;
    font-weight: 700;
}

.sheet-signatures {
    font-size: 0.65rem;
    line-height: 2;
    color: #111;
}

.sheet-signatures p {
    margin: 0;
}

@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .sheet-toolbar {
        display: none !important;
    }

    .sheet-document {
        padding: 0;
    }

    .sheet-table {
        font-size: 7pt;
    }

    .sheet-table th,
    .sheet-table td {
        padding: 1px;
    }

    .col-day {
        width: auto;
        height: 10px;
    }
}
</style>
