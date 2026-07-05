<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Services\BackupService;
use Illuminate\Console\Command;

class RunStorageBackupCommand extends Command
{
    protected $signature = 'storage:backup';

    protected $description = 'Create a zip archive of application storage directories';

    public function handle(BackupService $service): int
    {
        if (! config('backup.enabled') || ! config('backup.storage.enabled')) {
            $this->warn('Storage backups are disabled.');

            return self::FAILURE;
        }

        $backup = $service->createPending(BackupType::Storage, BackupTrigger::Scheduled);
        $backup = $service->run($backup);

        if ($backup->status === BackupStatus::Failed) {
            $this->error($backup->error_message ?? 'Storage backup failed.');

            return self::FAILURE;
        }

        $size = $backup->file_size !== null
            ? number_format($backup->file_size / 1024, 1).' KB'
            : 'unknown size';

        $this->info("Storage backup completed: {$backup->filename} ({$size})");

        return self::SUCCESS;
    }
}
