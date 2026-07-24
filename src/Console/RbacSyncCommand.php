<?php

namespace Dpb\MasterPermissionGuard\Console;

use Dpb\MasterPermissionGuard\Services\RbacSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RbacSyncCommand extends Command
{
    protected $signature = 'dpb:mpg:rbac-sync {--guard=} {--reset}';

    protected $description = 'Idempotently adds roles and permissions according to the configuration in dpb-mpg.php';

    public function handle(): int
    {
        $message = '';
        if ($this->option('reset')) {
            DB::table('role_has_permissions')
                ->delete();
            DB::table('permissions')
                ->delete();
            $message = 'Old permissions removed and ';
        }

        (new RbacSyncService)
            ->sync(config('dpb-mpg.rbac', []));

        $this->info($message.'MPG RBAC synced.');

        return self::SUCCESS;
    }
}
