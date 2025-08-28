<?php

namespace Dpb\MasterPermissionGuard\Database;

use App\Models\User;
use Dpb\MasterPermissionGuard\Exceptions\MissingPermissionException;
use Dpb\MasterPermissionGuard\Services\PermissionGuardService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;

final class GuardedQueryBuilder extends Builder
{
    private function guardOperation(
        string $operation
    ): void {
        if (!$this->can($operation)) {
            throw new MissingPermissionException(
                operation: $operation,
                table: $this->from
            );
        }
    }

    private function can(
        string $operation
    ): bool {
        $guarded = PermissionGuardService::getGuardedTables();

        if (!isset($guarded[$this->from])
            || !in_array($operation, $guarded[$this->from]['permissions'], true)
        ) {
            return true;
        }

        /** @var User|null $user */
        $user = Auth::user();
        return $user?->can("dpb-mpg.{$this->from}.{$operation}") === true;
    }

    public function get(
        $columns = ['*']
    ) {
        $this->guardOperation('read');
        return parent::get($columns);
    }

    public function first(
        $columns = ['*']
    ) {
        $this->guardOperation('read');
        return parent::first($columns);
    }

    public function count(
        $columns = '*'
    ) {
        $this->guardOperation('read');
        return parent::count($columns);
    }

    public function exists()
    {
        $this->guardOperation('read');
        return parent::exists();
    }

    public function insert(
        array $values
    ) {
        $this->guardOperation('create');
        return parent::insert($values);
    }

    public function upsert(
        array $values,
        array|string $uniqueBy,
        ?array $update = null
    ): int {
        $this->can('update') || $this->can('create');
        return parent::upsert($values, $uniqueBy, $update);
    }

    public function update(
        array $values
    ) {
        $this->guardOperation('update');
        return parent::update($values);
    }

    public function delete(
        $id = null
    ) {
        $this->guardOperation('delete');
        return parent::delete($id);
    }

    public function truncate()
    {
        $this->guardOperation('delete');
        return parent::truncate();
    }
}
