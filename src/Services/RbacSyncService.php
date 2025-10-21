<?php

namespace Dpb\MasterPermissionGuard\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RbacSyncService
{
    /** @param array<string, array<int, string>> $rolesToPerms role => [permission,...] */
    public function sync(
        array $config
    ): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () use ($config) {
            foreach ($config as $guard => $rolesToPerms) {
                foreach ($rolesToPerms as $roleName => $perms) {
                    $role = Role::findOrCreate($roleName, $guard);

                    foreach ($perms as $permName) {
                        $perm = Permission::findOrCreate($permName, $guard);
                        $role->givePermissionTo($perm);
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
