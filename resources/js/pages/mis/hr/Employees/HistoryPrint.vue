<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { onMounted } from 'vue';
import AppLogoImage from '@/components/AppLogoImage.vue';
import { Button } from '@/components/ui/button';
import MisExportActions from '@/components/mis/MisExportActions.vue';
import { useMisPage } from '@/composables/useMisPage';
import { formatDate } from '@/lib/format';

interface Deployment {
    id: number;
    role: string | null;
    status?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    project?: { id: number; code: string; name: string; location?: string | null } | null;
    project_site?: { id: number; name: string } | null;
}

interface StatusLog {
    id: number;
    from_status: string | null;
    to_status: string;
    reason?: string | null;
    created_at: string;
    changed_by?: { id: number; name: string } | null;
}

const props = defineProps<{
    employee: {
        id: number;
        first_name: string;
        last_name: string;
        father_name?: string | null;
        phone?: string | null;
        tazkira_number?: string | null;
        status: string;
        is_permanent: boolean;
        fire_date?: string | null;
        job_detail?: {
            designation?: string | null;
            hire_date?: string | null;
            department?: { name: string } | null;
        } | null;
        status_change_logs?: StatusLog[];
    };
    deployments: Deployment[];
    company: {
        name: string;
        name_fa: string;
        address: string;
        phone: string;
    };
    generated_on: string;
}>();

const { t } = useMisPage();

const fullName = `${props.employee.first_name} ${props.employee.last_name}`;

const statusLabel = (status: string | null): string => {
    if (!status) {
        return '—';
    }

    const labels: Record<string, string> = {
        active: t('Active'),
        inactive: t('Inactive'),
        terminated: t('Terminated'),
        blocked: t('Blocked'),
    };

    return labels[status] ?? status;
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('autoprint') === '1') {
        window.setTimeout(() => window.print(), 400);
    }
});
</script>

<template>
    <Head :title="t('Employee history')" />

    <div class="sheet-page">
        <div class="sheet-toolbar no-print">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/hr/employees/${employee.id}`">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <MisExportActions variant="outline" />
        </div>

        <article class="sheet-document" data-export-root>
            <header class="sheet-header">
                <AppLogoImage class="sheet-logo" />
                <div class="sheet-header-copy">
                    <h1>{{ company.name.toUpperCase() }}</h1>
                    <p class="sheet-fa">{{ company.name_fa }}</p>
                    <p class="sheet-contact">{{ company.address }} · {{ company.phone }}</p>
                </div>
            </header>

            <h2 class="sheet-title">{{ t('Employee history') }}</h2>

            <table class="meta-table">
                <tbody>
                    <tr>
                        <th>{{ t('Name') }}</th>
                        <td>{{ fullName }}</td>
                        <th>{{ t("Father's name") }}</th>
                        <td>{{ employee.father_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>{{ t('Tazkira number') }}</th>
                        <td>{{ employee.tazkira_number ?? '—' }}</td>
                        <th>{{ t('Phone') }}</th>
                        <td>{{ employee.phone ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>{{ t('Department') }}</th>
                        <td>{{ employee.job_detail?.department?.name ?? '—' }}</td>
                        <th>{{ t('Designation') }}</th>
                        <td>{{ employee.job_detail?.designation ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>{{ t('Hire date') }}</th>
                        <td>{{ formatDate(employee.job_detail?.hire_date) }}</td>
                        <th>{{ t('Fire date') }}</th>
                        <td>{{ formatDate(employee.fire_date) }}</td>
                    </tr>
                    <tr>
                        <th>{{ t('Type') }}</th>
                        <td>
                            {{
                                employee.is_permanent
                                    ? t('Permanent')
                                    : t('Project-based')
                            }}
                        </td>
                        <th>{{ t('Status') }}</th>
                        <td>{{ statusLabel(employee.status) }}</td>
                    </tr>
                </tbody>
            </table>

            <section class="sheet-section">
                <h3>{{ t('Work history') }}</h3>
                <p v-if="!deployments.length" class="sheet-empty">
                    {{ t('No work history.') }}
                </p>
                <table v-else class="sheet-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ t('Project') }}</th>
                            <th>{{ t('Site') }}</th>
                            <th>{{ t('Role') }}</th>
                            <th>{{ t('From') }}</th>
                            <th>{{ t('To') }}</th>
                            <th>{{ t('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in deployments" :key="row.id">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <span v-if="row.project">
                                    {{ row.project.code }} — {{ row.project.name }}
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td>{{ row.project_site?.name ?? row.project?.location ?? '—' }}</td>
                            <td>{{ row.role ?? '—' }}</td>
                            <td>{{ formatDate(row.start_date) }}</td>
                            <td>
                                {{
                                    row.end_date
                                        ? formatDate(row.end_date)
                                        : t('Ongoing')
                                }}
                            </td>
                            <td>{{ statusLabel(row.status ?? 'active') }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section
                v-if="employee.status_change_logs?.length"
                class="sheet-section"
            >
                <h3>{{ t('Employment Status History') }}</h3>
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th>{{ t('Date') }}</th>
                            <th>{{ t('From') }}</th>
                            <th>{{ t('To') }}</th>
                            <th>{{ t('Changed by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="log in employee.status_change_logs"
                            :key="log.id"
                        >
                            <td>{{ formatDate(log.created_at) }}</td>
                            <td>{{ statusLabel(log.from_status) }}</td>
                            <td>{{ statusLabel(log.to_status) }}</td>
                            <td>{{ log.changed_by?.name ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <p class="sheet-generated">
                {{ t('Printed on') }} {{ formatDate(generated_on) }}
            </p>

            <footer class="sheet-signatures">
                <p>{{ t('Prepared By') }}: ______________________</p>
                <p>{{ t('Approved By') }}: ______________________</p>
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
    align-items: center;
    max-width: 210mm;
    margin: 0 auto 0.75rem;
}

.sheet-document {
    max-width: 210mm;
    margin: 0 auto;
    background: #fff;
    padding: 1.25rem 1.5rem 1.75rem;
    color: #111;
}

.sheet-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #111;
}

.sheet-logo {
    width: 3.75rem;
    height: 3.75rem;
}

.sheet-header-copy h1 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.sheet-fa {
    margin: 0.15rem 0 0;
    font-size: 0.95rem;
}

.sheet-contact {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: #444;
}

.sheet-title {
    margin: 0 0 0.75rem;
    text-align: center;
    font-size: 1.15rem;
    font-weight: 700;
    text-transform: uppercase;
}

.meta-table,
.sheet-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}

.meta-table th,
.meta-table td,
.sheet-table th,
.sheet-table td {
    border: 1px solid #111;
    padding: 0.35rem 0.45rem;
    text-align: start;
    vertical-align: top;
}

.meta-table th,
.sheet-table th {
    background: #f3f4f6;
    font-weight: 700;
    white-space: nowrap;
}

.sheet-section {
    margin-top: 1rem;
}

.sheet-section h3 {
    margin: 0 0 0.45rem;
    font-size: 0.9rem;
    text-transform: uppercase;
}

.sheet-empty {
    margin: 0;
    font-size: 0.85rem;
    color: #444;
}

.sheet-generated {
    margin: 1rem 0 0;
    font-size: 0.75rem;
    color: #444;
}

.sheet-signatures {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-top: 1.75rem;
    font-size: 0.8rem;
}

.sheet-signatures p {
    margin: 0;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    .sheet-page {
        background: #fff;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }

    .sheet-document {
        max-width: none;
        padding: 0;
    }
}
</style>
