<?php

namespace Dpb\MasterPermissionGuard\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SqlInspector
{
    public static function getAffectedTables(
        string $sql
    ): array {

        if (preg_match('/\A\s*TRUNCATE\s+(?:TABLE\s+)?(?:`?([a-z0-9_]+)`?\.)?`?([a-z0-9_]+)`?/i', $sql, $m)) {
            $table = ($m[1] ?? null) ? "{$m[1]}.{$m[2]}" : $m[2];
            return [$table => 'delete'];
        }

        return (new Collection(DB::unprepared('EXPLAIN ' . $sql)))
            ->mapWithKeys(fn ($item) => [$item->table => match($item->select_type) {
                'SIMPLE' => 'read',
                'INSERT' => 'create',
                'REPLACE' => 'create',
                'UPDATE' => 'update',
                'DELETE' => 'delete',
                default => dd('unknown select type: '. $item->select_type)
            }])
            ->toArray();
    }
}
