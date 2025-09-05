<?php

namespace Dpb\MasterPermissionGuard\Http\Middleware;

use Dpb\MasterPermissionGuard\Database\GuardedMySqlConnection;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\DatabaseManager;

class EnableMasterPermissionGuard
{
    public function __construct(
        protected readonly DatabaseManager $db
    ) {
    }

    public function handle(
        $request,
        \Closure $next
    ) {
        config(['dpb-mpg.enabled' => true]);

        $this->db->extend('mysql', function (array $config, string $name) {
            $pdo = (new MySqlConnector())->connect($config);
            return new GuardedMySqlConnection(
                $pdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config
            );
        });
        $this->db->purge(config('database.default'));

        return $next($request);
    }
}
