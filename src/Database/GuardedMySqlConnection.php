<?php

namespace Dpb\ModelPermissionGuard\Database;

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
