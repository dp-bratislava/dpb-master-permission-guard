<?php

namespace Dpb\MasterPermissionGuard\Database;

use Dpb\MasterPermissionGuard\Services\PermissionGuardService;
use Dpb\MasterPermissionGuard\Support\SqlInspector;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Str;

final class GuardedMySqlConnection extends MySqlConnection
{
    public function query(): GuardedQueryBuilder
    {
        return new GuardedQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    public function select(
        $query,
        $bindings = [],
        $useReadPdo = true
    ) {
        $this->guardSql($query, $bindings);
        return parent::select($query, $bindings, $useReadPdo);
    }

    public function insert(
        $query,
        $bindings = [],
        $sequence = null
    ): bool {
        $this->guardSql($query, $bindings);
        return parent::insert($query, $bindings);
    }

    public function update($query, $bindings = [])
    {
        $this->guardSql($query, $bindings);
        return parent::update($query, $bindings);
    }

    public function delete($query, $bindings = [])
    {
        $this->guardSql($query, $bindings);
        return parent::delete($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        $this->guardSql($query, $bindings);
        return parent::affectingStatement($query, $bindings);
    }

    public function statement($query, $bindings = [])
    {
        $this->guardSql($query, $bindings);
        return parent::statement($query, $bindings);
    }

    public function unprepared($query)
    {
        $this->guardSql($query);
        return parent::select($query);
    }

    protected function guardSql(
        string $sql,
        array $bindings = []
    ): void {

        if (strpos($sql, 'EXPLAIN') === 0) {
            return;
        }

        if (!Str::contains($sql, array_keys(PermissionGuardService::getGuardedTables()))) {
            return;
        }

        foreach (SqlInspector::getAffectedTables($sql, $bindings) as $table => $operation) {
            PermissionGuardService::authorize($table, $operation);
        }

    }
}
