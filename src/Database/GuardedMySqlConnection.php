<?php

namespace Dpb\MasterPermissionGuard\Database;

use Illuminate\Database\MySqlConnection;

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
}
