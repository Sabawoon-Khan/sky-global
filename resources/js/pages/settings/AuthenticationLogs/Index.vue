<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ShieldAlert, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import MisPagination from '@/components/MisPagination.vue';
import { Badge } from '@/components/ui/badge';
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
import { formatDate, type Paginated } from '@/lib/format';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface AuthenticationLogRecord {
    id: number;
    user_id: number | null;
    email: string | null;
    event: string;
    success: boolean;
    failure_reason: string | null;
    ip_address: string | null;
    ip_addresses: string[] | null;
    user_agent: string | null;
    device_type: string | null;
    browser: string | null;
    platform: string | null;
    session_id: string | null;
    guard: string;
    request_method: string | null;
    request_path: string | null;
    referer: string | null;
    accept_language: string | null;
    metadata: Record<string, unknown> | null;
    logged_at: string;
    user?: UserSummary | null;
}

interface Props {
    logs: Paginated<AuthenticationLogRecord>;
    events: string[];
    filters?: {
        search?: string | null;
        event?: string | null;
        success?: string | null;
    };
}

const props = defineProps<Props>();

const { t } = useTranslations();
const expandedLogId = ref<number | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Login Logs', href: '/settings/login-logs' },
        ],
    },
});

const toggleExpanded = (logId: number): void => {
    expandedLogId.value = expandedLogId.value === logId ? null : logId;
};

const eventLabel = (event: string): string => {
    const labels: Record<string, string> = {
        login_success: t('Successful login'),
        login_failed: t('Failed login'),
        logout: t('Logout'),
        two_factor_challenged: t('Two-factor challenge'),
        two_factor_failed: t('Failed two-factor'),
        lockout: t('Rate limited'),
        session_revoked_inactive: t('Inactive session revoked'),
    };

    return labels[event] ?? event.replace(/_/g, ' ');
};

const failureReasonLabel = (reason: string | null): string => {
    if (!reason) {
        return '—';
    }

    const labels: Record<string, string> = {
        invalid_credentials: t('Invalid credentials'),
        invalid_two_factor_code: t('Invalid two-factor code'),
        rate_limited: t('Too many attempts'),
        account_inactive: t('Account inactive'),
    };

    return labels[reason] ?? reason.replace(/_/g, ' ');
};

const eventVariant = (
    event: string,
    success: boolean,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (event === 'login_success' || event === 'logout') {
        return 'default';
    }

    if (event === 'two_factor_challenged') {
        return 'secondary';
    }

    return success ? 'outline' : 'destructive';
};

const displayName = (log: AuthenticationLogRecord): string =>
    log.user?.name ?? log.email ?? t('Unknown user');

const metadataEntries = (log: AuthenticationLogRecord): Array<[string, string]> => {
    const entries: Array<[string, string]> = [];

    if (log.browser) {
        entries.push([t('Browser'), log.browser]);
    }

    if (log.platform) {
        entries.push([t('Platform'), log.platform]);
    }

    if (log.device_type) {
        entries.push([t('Device'), log.device_type]);
    }

    if (log.request_method && log.request_path) {
        entries.push([t('Request'), `${log.request_method} ${log.request_path}`]);
    }

    if (log.referer) {
        entries.push([t('Referer'), log.referer]);
    }

    if (log.accept_language) {
        entries.push([t('Language'), log.accept_language]);
    }

    if (log.session_id) {
        entries.push([t('Session ID'), log.session_id]);
    }

    if (log.ip_addresses?.length) {
        entries.push([t('All IPs'), log.ip_addresses.join(', ')]);
    }

    const remember = log.metadata?.remember;

    if (typeof remember === 'boolean') {
        entries.push([t('Remember me'), remember ? t('Yes') : t('No')]);
    }

    const headers = log.metadata?.headers;

    if (headers && typeof headers === 'object' && !Array.isArray(headers)) {
        for (const [key, value] of Object.entries(headers)) {
            if (typeof value === 'string' && value !== '') {
                entries.push([key, value]);
            }
        }
    }

    return entries;
};

const hasLogs = computed(() => props.logs.data.length > 0);
</script>

<template>
    <Head :title="t('Login Logs')" />

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShieldAlert class="size-5" />
                    {{ t('Login Logs') }}
                </CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Security audit trail for sign-in, sign-out, and authentication events',
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <form
                    method="get"
                    action="/settings/login-logs"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div class="relative md:col-span-2">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters?.search ?? ''"
                            :placeholder="t('Search by user, email, IP, or user agent...')"
                            class="pl-9"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="event">{{ t('Event') }}</Label>
                        <select
                            id="event"
                            name="event"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :value="filters?.event ?? ''"
                        >
                            <option value="">{{ t('All events') }}</option>
                            <option v-for="event in events" :key="event" :value="event">
                                {{ eventLabel(event) }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="success">{{ t('Result') }}</Label>
                        <select
                            id="success"
                            name="success"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :value="filters?.success ?? ''"
                        >
                            <option value="">{{ t('All results') }}</option>
                            <option value="1">{{ t('Success') }}</option>
                            <option value="0">{{ t('Failed') }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground"
                        >
                            {{ t('Apply filters') }}
                        </button>
                    </div>
                </form>

                <div
                    v-if="!hasLogs"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    {{ t('No login logs found.') }}
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="log in logs.data"
                        :key="log.id"
                        class="rounded-lg border"
                    >
                        <button
                            type="button"
                            class="flex w-full items-start justify-between gap-4 p-4 text-left"
                            @click="toggleExpanded(log.id)"
                        >
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ displayName(log) }}</span>
                                    <Badge
                                        :variant="eventVariant(log.event, log.success)"
                                    >
                                        {{ eventLabel(log.event) }}
                                    </Badge>
                                    <Badge :variant="log.success ? 'default' : 'destructive'">
                                        {{ log.success ? t('Success') : t('Failed') }}
                                    </Badge>
                                </div>

                                <div
                                    class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground"
                                >
                                    <span>{{ formatDate(log.logged_at) }}</span>
                                    <span v-if="log.ip_address">
                                        {{ t('IP') }}: {{ log.ip_address }}
                                    </span>
                                    <span v-if="log.browser">{{ log.browser }}</span>
                                    <span v-if="log.platform">{{ log.platform }}</span>
                                </div>

                                <p
                                    v-if="log.failure_reason"
                                    class="text-sm text-destructive"
                                >
                                    {{ failureReasonLabel(log.failure_reason) }}
                                </p>
                            </div>

                            <component
                                :is="expandedLogId === log.id ? ChevronUp : ChevronDown"
                                class="mt-1 size-4 shrink-0 text-muted-foreground"
                            />
                        </button>

                        <div
                            v-if="expandedLogId === log.id"
                            class="space-y-4 border-t bg-muted/20 p-4 text-sm"
                        >
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <p class="font-medium">{{ t('User') }}</p>
                                    <p class="text-muted-foreground">
                                        {{ log.user?.name ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-medium">{{ t('Email') }}</p>
                                    <p class="text-muted-foreground">
                                        {{ log.email ?? log.user?.email ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="log.user_agent">
                                <p class="font-medium">{{ t('User agent') }}</p>
                                <p class="break-all text-muted-foreground">
                                    {{ log.user_agent }}
                                </p>
                            </div>

                            <div
                                v-if="metadataEntries(log).length"
                                class="grid gap-3 md:grid-cols-2"
                            >
                                <div
                                    v-for="[label, value] in metadataEntries(log)"
                                    :key="`${log.id}-${label}`"
                                >
                                    <p class="font-medium">{{ label }}</p>
                                    <p class="break-all text-muted-foreground">
                                        {{ value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <MisPagination :pagination="logs" />
            </CardContent>
        </Card>
    </div>
</template>
