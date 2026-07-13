<?php

namespace App\Console\Commands;

use App\Services\MisPermissionRegistrar;
use Illuminate\Console\Command;

class SyncMisPermissionsCommand extends Command
{
    protected $signature = 'mis:sync-permissions';

    protected $description = 'Create or update MIS permissions from config/mis_permissions.php';

    public function handle(MisPermissionRegistrar $registrar): int
    {
        $permissions = $registrar->sync();

        $this->info(sprintf(
            'Synced %d permission(s) from config/mis_permissions.php.',
            count($permissions),
        ));

        return self::SUCCESS;
    }
}
