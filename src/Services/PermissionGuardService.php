<?php

namespace Dpb\MasterPermissionGuard\Services;

use App\Models\User;
use Dpb\MasterPermissionGuard\Exceptions\MissingPermissionException;
use Dpb\MasterPermissionGuard\Support\Registry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermissionGuardService
{
    public static function getGuardedTables(): array
    {
        return Registry::tables();
    }

    public static function can(
        string $table,
        string $operation
    ): bool {
        return self::getAuthUser()
            ?->can(self::buildPermissionKey($table, $operation));
    }

    public static function authorize(
        string $table,
        string $operation
    ): void {
        if (!Registry::isProtected($table)) {
            return;
        }

        if (!self::getAuthUser()?->can(self::buildPermissionKey($table, $operation))) {
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

        if (!self::getAuthUser()?->canAny(array_map(fn ($op) => self::buildPermissionKey($table, $op), $operations))) {
            throw new MissingPermissionException(
                operation: implode(', ', $operations),
                table: $table
            );
        }
    }

    public static function buildPermissionKey(
        string $table,
        string $operation
    ): string {
        return sprintf(
            'dpb-mpg.%s.%s',
            self::sanitizeTableName($table),
            $operation
        );
    }

    public static function findRoles(): array
    {
        return DB::table('roles')
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function findAvailableGuards(): array
    {
        return DB::table('permissions')
            ->distinct()
            ->pluck('guard_name')
            ->mapWithKeys(fn ($guard) => [$guard => $guard])
            ->toArray();
    }

    public static function findAvailableTables(): array
    {
        return DB::table('permissions')
            ->pluck('name')
            ->filter(fn ($name) => str_starts_with($name, 'dpb-mpg.'))
            ->map(fn ($name) => explode('.', $name)[1])
            ->unique()
            ->mapWithKeys(fn ($table) => [$table => $table])
            ->toArray();
    }

    public static function findAvailablePackages(): array
    {
        return DB::table('permissions')
            ->pluck('name')
            ->map(fn ($name) => explode('.', $name)[0])
            ->unique()
            ->filter(fn ($packageName) => $packageName !== 'dpb-mpg')
            ->mapWithKeys(fn ($package) => [$package => $package])
            ->toArray();
    }

    public static function findTablePermissions(): Collection
    {
        return DB::table('permissions')
            ->where('name', 'like', 'dpb-mpg.%')
            ->get();
    }

    public static function findPagePermissions(): Collection
    {
        return DB::table('permissions')
            ->where('name', 'like', '%.page-access.%')
            ->get();
    }

    public static function findComponentPermissions(): Collection
    {
        return DB::table('permissions')
            ->where('name', 'like', '%.component-access.%')
            ->get();
    }

    public static function findOtherPermissions(): Collection
    {
        return DB::table('permissions')
            ->whereNot('name', 'like', 'dpb-mpg.%')
            ->whereNot('name', 'like', '%.page-access.%')
            ->whereNot('name', 'like', '%.component-access.%')
            ->get();
    }

    public static function findPermissions(
        string $guard = 'web',
        ?string $package = 'dpb-mpg',
        ?string $table = null,
        bool $withRoles = false
    ): array {
        $name = sprintf('%s.%s', $package ?: '%', $table ?: '%');
        if (!str_ends_with($name, '%')) {
            $name .= '.%';
        }
        $tableQuery = DB::table('permissions AS p');
        if ($withRoles) {
            $tableQuery
                ->leftJoin('role_has_permissions AS rhp', 'rhp.permission_id', 'p.id')
                ->leftJoin('roles AS r', 'r.id', 'rhp.role_id');
        }
        if (empty($package)) {
            $tableQuery
                ->whereNotLike('p.name', 'dpb-mpg.%');
        }
        $tableQuery
            ->where('p.guard_name', $guard)
            ->where('p.name', 'like', $name)
            ->groupBy('p.id')
            ->orderBy('p.id')
            ->selectRaw(
                $withRoles
                ? " p.id, p.name, p.guard_name,
                    CASE WHEN COUNT(r.id)=0
                        THEN JSON_ARRAY()
                        ELSE JSON_ARRAYAGG(JSON_OBJECT('id', r.id, 'name', r.name))
                    END AS roles"
                : 'p.id, p.name, p.guard_ name'
            );
        return $tableQuery
            ->get()
            ->map(function ($row) use ($withRoles) {
                if ($withRoles) {
                    $row->roles = json_decode($row->roles, true);
                }
                return (array) $row;
            })
            ->toArray();
    }

    private static function sanitizeTableName(
        string $table
    ): string {
        return explode(' as ', $table)[0];
    }

    private static function getAuthUser(): ?User
    {
        return Auth::user();
    }
}
