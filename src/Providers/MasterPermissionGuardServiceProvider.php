<?php

namespace Dpb\MasterPermissionGuard\Providers;

use Dpb\MasterPermissionGuard\Console\DiscoverProtectedModels;
use Dpb\MasterPermissionGuard\Database\GuardedMySqlConnection;
use Dpb\MasterPermissionGuard\Support\Registry;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\ServiceProvider;

class MasterPermissionGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerGuardedMySqlConnection();
    }

    public function boot(): void
    {
        $this->initializeCache();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'dpb-mpg');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dpb-mpg');
        $this->registerConsoleCommands();
    }

    private function registerGuardedMySqlConnection(): void
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('mysql', function (array $config, string $name) {
                $pdo = (new MySqlConnector())->connect($config);
                return new GuardedMySqlConnection(
                    $pdo,
                    $config['database'],
                    $config['prefix'] ?? '',
                    $config
                );
            });
        });
        return;

        $this->app['db']->extend('mysql', function (
            array $config,
            string $name
        ) {
            $pdo  = (new MySqlConnector())
                ->connect($config);
            $conn = new GuardedMySqlConnection(
                $pdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config
            );
            return $conn;
        });
    }

    private function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DiscoverProtectedModels::class]);
        }
    }

    private function initializeCache(): void
    {
        Registry::seed(cache()->get('mpg:tables', []));
    }
}
