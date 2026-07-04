<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    Archive,
    Clock,
    Database,
    Download,
    HardDrive,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { formatDate, formatFileSize } from '@/lib/format';

const { t } = useTranslations();

interface BackupRecord {
    id: number;
    type: 'storage' | 'database';
    filename: string;
    file_size: number | null;
    status: 'pending' | 'running' | 'completed' | 'failed';
    trigger: 'manual' | 'scheduled';
    error_message: string | null;
    created_by: string | null;
    download_url: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string | null;
}

const props = defineProps<{
    backups: BackupRecord[];
    backupEnabled: boolean;
    storageBackupEnabled: boolean;
    databaseBackupEnabled: boolean;
    retentionDays: number;
    retentionCount: number;
    hasRemoteDisk: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Backups', href: '/settings/backups' },
        ],
    },
});

const statusVariant = (
    status: BackupRecord['status'],
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'running':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const typeLabel = (type: BackupRecord['type']): string =>
    type === 'storage' ? t('Storage') : t('Database');

const statusLabel = (status: BackupRecord['status']): string => t(status);

const triggerLabel = (trigger: BackupRecord['trigger']): string => t(trigger);

const completedStorageCount = computed(
    () =>
        props.backups.filter(
            (backup) =>
                backup.type === 'storage' && backup.status === 'completed',
        ).length,
);

const completedDatabaseCount = computed(
    () =>
        props.backups.filter(
            (backup) =>
                backup.type === 'database' && backup.status === 'completed',
        ).length,
);

const deleteBackup = (backup: BackupRecord): void => {
    if (
        !confirm(
            t('Delete ":name"? This backup file will be permanently removed.', {
                name: backup.filename,
            }),
        )
    ) {
        return;
    }

    router.delete(`/settings/backups/${backup.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('Backups')" />

    <div class="w-full space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <HardDrive class="size-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ completedStorageCount }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Storage backups') }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Database class="size-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ completedDatabaseCount }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Database backups') }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                    >
                        <Archive class="size-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ retentionCount }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Max per type') }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                    >
                        <Clock class="size-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ retentionDays }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Days before expiry') }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="w-full">
            <CardHeader>
                <CardTitle>{{ t('Backup history') }}</CardTitle>
                <CardDescription>
                    {{ t('View, download, and manage storage and database backups') }}
                </CardDescription>
                <CardAction v-if="backupEnabled">
                    <div class="flex flex-wrap gap-2">
                        <Form
                            v-if="storageBackupEnabled"
                            action="/settings/backups"
                            method="post"
                            v-slot="{ processing }"
                        >
                            <input type="hidden" name="type" value="storage" />
                            <Button
                                type="submit"
                                :disabled="processing"
                                variant="outline"
                                size="sm"
                            >
                                <HardDrive class="size-4" />
                                {{ t('Backup storage') }}
                            </Button>
                        </Form>
                        <Form
                            v-if="databaseBackupEnabled"
                            action="/settings/backups"
                            method="post"
                            v-slot="{ processing }"
                        >
                            <input type="hidden" name="type" value="database" />
                            <Button type="submit" :disabled="processing" size="sm">
                                <Database class="size-4" />
                                {{ t('Backup database') }}
                            </Button>
                        </Form>
                    </div>
                </CardAction>
            </CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="!backupEnabled"
                    class="px-6 pb-6 text-sm text-muted-foreground"
                >
                    {{ t('Backups are disabled. Set BACKUP_ENABLED=true in your environment to enable them.') }}
                </div>

                <div
                    v-else-if="backups.length === 0"
                    class="flex flex-col items-center gap-3 px-6 py-16 text-center text-muted-foreground"
                >
                    <Archive class="size-10 opacity-50" />
                    <p class="text-sm">
                        {{ t('No backups yet. Create a storage or database backup to get started.') }}
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40 text-left text-muted-foreground">
                                <th class="px-6 py-3 font-medium">{{ t('Type') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('File') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Size') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Trigger') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Created') }}</th>
                                <th class="px-6 py-3 text-right font-medium">
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="backup in backups"
                                :key="backup.id"
                                class="border-b last:border-b-0 hover:bg-muted/20"
                            >
                                <td class="px-6 py-4">
                                    <Badge variant="outline">
                                        {{ typeLabel(backup.type) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-medium">
                                        {{ backup.filename }}
                                    </div>
                                    <div
                                        v-if="backup.created_by"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('by :name', { name: backup.created_by }) }}
                                    </div>
                                    <div
                                        v-if="backup.error_message"
                                        class="mt-1 text-xs text-destructive"
                                    >
                                        {{ backup.error_message }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <Badge
                                        :variant="statusVariant(backup.status)"
                                    >
                                        {{ statusLabel(backup.status) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 tabular-nums">
                                    {{ formatFileSize(backup.file_size) }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ triggerLabel(backup.trigger) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    {{ formatDate(backup.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Button
                                            v-if="backup.download_url"
                                            variant="outline"
                                            size="sm"
                                            as-child
                                        >
                                            <a :href="backup.download_url">
                                                <Download class="size-4" />
                                                {{ t('Download') }}
                                            </a>
                                        </Button>
                                        <Button
                                            v-if="
                                                backup.status !== 'pending' &&
                                                backup.status !== 'running'
                                            "
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteBackup(backup)"
                                        >
                                            <Trash2 class="size-4" />
                                            {{ t('Delete') }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
