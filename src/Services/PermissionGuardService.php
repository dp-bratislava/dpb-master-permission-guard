<?php

namespace Dpb\MasterPermissionGuard\Services;

use App\Models\User;
use Dpb\MasterPermissionGuard\Exceptions\MissingPermissionException;
use Dpb\MasterPermissionGuard\Support\Registry;
use Illuminate\Support\Facades\Auth;

class PermissionGuardService
{
    public static function getGuardedTables(): array
    {
        return Registry::tables();
    }

    public static function authorize(
        string $table,
        string $operation
    ): void {
        if (!Registry::isProtected($table)) {
            return;
        }

        if (!self::getAuthUser()?->can("dpb-mpg.{$table}.{$operation}")) {
            throw new MissingPermissionException(
                operation: $operation,
                table: $table
            );
        }
    }

    public static function authorizeOneOf(
        string $table,
        array $operations
    ): void {
        if (!Registry::isProtected($table)) {
            return;
        }

        if (!self::getAuthUser()?->canAny(array_map(fn ($op) => "dpb-mpg.{$table}.{$op}", $operations))) {
            throw new MissingPermissionException(
                operation: implode(', ', $operations),
                table: $table
            );
        }
    }

    private static function getAuthUser(): ?User
    {
        return Auth::user();
    }
}
