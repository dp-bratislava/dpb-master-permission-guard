<?php

namespace Dpb\MasterPermissionGuard\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @todo SqlInspector hardenings:
 * - Handle statements where EXPLAIN is invalid: TRUNCATE, DDL (CREATE|ALTER|DROP|RENAME),
 *   LOAD DATA, LOCK/UNLOCK, CALL, SET/SHOW/DO, OPTIMIZE/ANALYZE/REPAIR. Map to CRUD or block.
 * - INSERT/REPLACE ... VALUES: EXPLAIN returns "No tables used"; extract target table from SQL.
 * - INSERT/REPLACE ... SELECT: include target table + all source tables from EXPLAIN.
 * - Multi-table forms: DELETE t1,t2 ... JOIN ..., UPDATE ... JOIN ...; return all affected tables.
 * - Always pass $bindings to EXPLAIN; never use DB::unprepared() for it.
 * - Keep internal EXPLAIN bypass explicit (prefix "EXPLAIN ") or tag with a marker comment.
 * - Normalize SQL before detection: strip comments (/*...* /, --, #), handle /*! ... * /, trim.
 * - Reject multi-statement SQL; enforce single statement per call.
 * - Support schema-qualified and quoted identifiers consistently.
 * - Add unit tests covering all cases above.
 */
class SqlInspector
{
    public static function getAffectedTables(
        string $sql,
        array $bindings = []
    ): array {

        if (preg_match('/\A\s*TRUNCATE\s+(?:TABLE\s+)?(?:`?([a-z0-9_]+)`?\.)?`?([a-z0-9_]+)`?/i', $sql, $m)) {
            $table = ($m[1] ?? null) ? "{$m[1]}.{$m[2]}" : $m[2];
            return [$table => 'delete'];
        }

        return (new Collection(DB::select('EXPLAIN ' . $sql, $bindings)))
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
