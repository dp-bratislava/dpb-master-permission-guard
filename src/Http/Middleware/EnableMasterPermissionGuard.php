<?php

namespace Dpb\MasterPermissionGuard\Http\Middleware;

use Dpb\MasterPermissionGuard\Database\GuardedMySqlConnection;
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
            $connector = app('db.connector.mysql');
            $pdo = $connector->connect($config);
            return new GuardedMySqlConnection(
                $pdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config
            );
        });
        //$this->db->purge(config('database.default'));
        $this->db->connection(config('database.default'))
            ->reconnect();

        return $next($request);
    }
}
