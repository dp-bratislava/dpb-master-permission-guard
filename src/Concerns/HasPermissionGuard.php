<?php

namespace Dpb\MasterPermissionGuard\Concerns;

use Dpb\MasterPermissionGuard\Services\PermissionGuardService;

/**
 * Trait for models that require permission guarding.
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPermissionGuard
{
    protected static function bootHasPermissionGuard(): void
    {
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
