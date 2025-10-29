<?php

namespace Dpb\MasterPermissionGuard\Concerns;

use Dpb\MasterPermissionGuard\Services\PermissionGuardService;

/**
 * Trait for models that require permission guarding.
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasTableGuard
{
    protected static function bootHasTableGuard(): void
    {
    }

    public static function getTablePermissions(
        bool $readOnly = false,
        bool $withRestore = false
    ): array {
        $tablePermissions = [];
        $tableName = (new static())->getTable();
        $prefix = 'dpb-mpg';
        if ($readOnly) {
            return [
                "{$prefix}.{$tableName}.read"
            ];
        }
        $tablePermissions[] = "{$prefix}.{$tableName}.create";
        $tablePermissions[] = "{$prefix}.{$tableName}.read";
        $tablePermissions[] = "{$prefix}.{$tableName}.update";
        $tablePermissions[] = "{$prefix}.{$tableName}.delete";
        if ($withRestore) {
            $tablePermissions[] = "{$prefix}.{$tableName}.restore";
        }
        return $tablePermissions;
    }

    public static function canRead(): bool
    {
        return PermissionGuardService::can((new static())->getTable(), 'read');
    }

    public static function canCreate(): bool
    {
        return PermissionGuardService::can((new static())->getTable(), 'create');
    }

    public static function canUpdate(): bool
    {
        return PermissionGuardService::can((new static())->getTable(), 'update');
    }

    public static function canDelete(): bool
    {
        return PermissionGuardService::can((new static())->getTable(), 'delete');
    }
}
