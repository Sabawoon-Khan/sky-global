<?php

namespace App\Jobs;

use App\Models\StorageBackup;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunBackupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public StorageBackup $storageBackup) {}

    public function handle(BackupService $service): void
    {
        $service->run($this->storageBackup);
    }
}
