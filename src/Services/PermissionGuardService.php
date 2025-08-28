<?php

namespace Dpb\MasterPermissionGuard\Services;

use Dpb\MasterPermissionGuard\Support\Registry;

class PermissionGuardService
{
    public static function getGuardedTables(): array
    {
        return Registry::tables();
    }
}
