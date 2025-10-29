<?php

namespace Dpb\MasterPermissionGuard\Support;

final class Registry
{
    /** @var array<string,string> normalized_table => FQCN of model */
    private static array $tables = [];

    /** @param array<string,string> $map table => model */
    public static function seed(
        array $map
    ): void {
        self::$tables = [];
        foreach ($map as $table => $model) {
            self::$tables[strtolower($table)] = $model;
        }
    }

    public static function tables(): array
    {
        return self::$tables;
    }

    public static function isProtected(
        string $table,
        string $prefix = ''
    ): bool {
        $t = self::normalize($table, $prefix);
        return isset(self::$tables[$t]);
    }

    public static function modelFor(
        string $table,
        string $prefix = ''
    ): ?string {
        $t = self::normalize($table, $prefix);
        return self::$tables[$t] ?? null;
    }

    private static function normalize(
        string $from,
        string $prefix = ''
    ): string {
        $t = strtolower(trim($from));
        $t = preg_replace('/[`"\\[\\]]/', '', $t) ?: $t;
        $t = preg_split('/\\s+as\\s+|\\s+/', $t)[0];
        if ($prefix && str_starts_with($t, $prefix)) {
            $t = substr($t, strlen($prefix));
        }
        return $t;
    }
}
