<?php

namespace Dpb\ModelPermissionGuard\Services;

use Dpb\ModelPermissionGuard\Support\Registry;

class PermissionGuardService
{
    public static function getGuardedTables(): array
    {
        return Registry::tables();
    }
}
