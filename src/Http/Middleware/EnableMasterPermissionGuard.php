<?php

namespace Dpb\MasterPermissionGuard\Http\Middleware;

use Dpb\MasterPermissionGuard\Database\GuardedMySqlConnection;
use Illuminate\Database\DatabaseManager;

class EnableMasterPermissionGuard
{
    public function __construct(
        protected readonly DatabaseManager $db
    ) {}

    public function handle(
        $request,
        \Closure $next
    ): mixed {
        try {
            config(key: ['dpb-mpg.enabled' => true]);

            $this->db->extend(name: 'mysql', resolver: function (array $config, string $name): GuardedMySqlConnection {
                $connector = app(abstract: 'db.connector.mysql');
                $pdo = $connector->connect(config: $config);

                return new GuardedMySqlConnection(
                    pdo: $pdo,
                    database: $config['database'],
                    tablePrefix: $config['prefix'] ?? '',
                    config: $config
                );
            });
            // $this->db->purge(config('database.default'));
            $this->db->connection(name: config(key: 'database.default'))
                ->reconnect();
        } catch (\Throwable $e) {
            report(exception: $e);

            return response()->json(data: ['error' => 'Failed to enable master permission guard.'], status: 500);
        }

        return $next($request);
    }
}
