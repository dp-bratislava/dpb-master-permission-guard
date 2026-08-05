<?php

namespace Dpb\MasterPermissionGuard\Concerns;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Guard for Filament Resources.
 *
 * Hides a resource from navigation and blocks the List page unless the
 * authenticated user holds the generated page-access permission:
 *   <vendor>-<package>.page-access.<resource-class>
 *
 * Drop-in analogue of HasPageGuard for Resources. Note: overriding
 * canViewAny() replaces Filament's Model Policy check for viewAny; record
 * level CRUD (canEdit/canDelete/canView) still delegates to policies.
 */
trait HasResourceGuard
{
    public static function canViewAny(): bool
    {
        return Gate::allows(ability: self::getAccessPermission());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Gate::allows(ability: self::getAccessPermission());
    }

    public static function getAccessPermission(): string
    {
        $fqcnParts = explode(separator: '\\', string: self::class);
        if (str_starts_with(haystack: $fqcnParts[1], needle: $fqcnParts[0])) {
            $fqcnParts[1] = str_replace(search: $fqcnParts[0], replace: '', subject: $fqcnParts[1]);
        }

        return sprintf(
            '%s-%s.page-access.%s',
            Str::kebab(value: $fqcnParts[0]),
            Str::kebab(value: $fqcnParts[1]),
            Str::kebab(value: end(array: $fqcnParts))
        );
    }
}
