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
        return self::can(ability: 'read');
    }

    public static function canCreate(): bool
    {
        return self::can(ability: 'create');
    }

    public static function canUpdate(): bool
    {
        return self::can(ability: 'update');
    }

    public static function canDelete(): bool
    {
        return self::can(ability: 'delete');
    }

    public static function can(
        string $ability
    ): bool {
        return PermissionGuardService::can(
            table: (new static())->getTable(),
            operation: $ability
        );
    }
}
