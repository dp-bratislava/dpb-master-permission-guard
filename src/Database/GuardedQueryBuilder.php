<?php

namespace Dpb\MasterPermissionGuard\Database;

use Dpb\MasterPermissionGuard\Services\PermissionGuardService;
use Illuminate\Database\Query\Builder;

final class GuardedQueryBuilder extends Builder
{
    public function get(
        $columns = ['*']
    ) {
        PermissionGuardService::authorize($this->from, 'read');

        return parent::get($columns);
    }

    public function first(
        $columns = ['*']
    ) {
        PermissionGuardService::authorize($this->from, 'read');

        return parent::first($columns);
    }

    public function count(
        $columns = '*'
    ) {
        PermissionGuardService::authorize($this->from, 'read');

        return parent::count($columns);
    }

    public function exists()
    {
        PermissionGuardService::authorize($this->from, 'read');

        return parent::exists();
    }

    public function insert(
        array $values
    ) {
        PermissionGuardService::authorize($this->from, 'create');

        return parent::insert($values);
    }

    public function upsert(
        array $values,
        array|string $uniqueBy,
        ?array $update = null
    ): int {
        PermissionGuardService::authorizeOneOf($this->from, ['update', 'create']);

        return parent::upsert($values, $uniqueBy, $update);
    }

    public function update(
        array $values
    ) {
        PermissionGuardService::authorize($this->from, 'update');

        return parent::update($values);
    }

    public function delete(
        $id = null
    ) {
        PermissionGuardService::authorize($this->from, 'delete');

        return parent::delete($id);
    }

    public function truncate()
    {
        PermissionGuardService::authorize($this->from, 'delete');

        return parent::truncate();
    }
}
