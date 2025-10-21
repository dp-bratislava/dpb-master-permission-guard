<?php

namespace Dpb\MasterPermissionGuard\Concerns;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

trait HasPageGuard
{
    public static function canAccess(): bool
    {
        return Gate::allows(self::getAccessPermission());
    }

    public static function getAccessPermission(): string
    {
        $fqcnParts = explode('\\', self::class);
        if (str_starts_with($fqcnParts[1], $fqcnParts[0])) {
            $fqcnParts[1] = str_replace($fqcnParts[0], '', $fqcnParts[1]);
        }
        return sprintf(
            '%s-%s.page-access.%s',
            Str::kebab($fqcnParts[0]),
            Str::kebab($fqcnParts[1]),
            Str::kebab(end($fqcnParts))
        );
    }
}
