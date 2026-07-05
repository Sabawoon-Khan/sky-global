<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Services\BackupService;
use Illuminate\Console\Command;

class RunDatabaseBackupCommand extends Command
{
    protected $signature = 'database:backup';

    protected $description = 'Create a backup of the application database';

    public function handle(BackupService $service): int
    {
        if (! config('backup.enabled') || ! config('backup.database.enabled')) {
            $this->warn('Database backups are disabled.');

            return self::FAILURE;
        }

        $backup = $service->createPending(BackupType::Database, BackupTrigger::Scheduled);
        $backup = $service->run($backup);

        if ($backup->status === BackupStatus::Failed) {
            $this->error($backup->error_message ?? 'Database backup failed.');

            return self::FAILURE;
        }

        $size = $backup->file_size !== null
            ? number_format($backup->file_size / 1024, 1).' KB'
            : 'unknown size';

        $this->info("Database backup completed: {$backup->filename} ({$size})");

        return self::SUCCESS;
    }
}
