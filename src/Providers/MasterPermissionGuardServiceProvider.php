<?php

namespace Dpb\MasterPermissionGuard\Providers;

use Dpb\MasterPermissionGuard\Console\DiscoverProtectedModels;
use Dpb\MasterPermissionGuard\Console\RbacSyncCommand;
use Dpb\MasterPermissionGuard\Filament\Plugins\DpbMpgPlugin;
use Dpb\MasterPermissionGuard\Http\Middleware\EnableMasterPermissionGuard;
use Dpb\MasterPermissionGuard\Support\Registry;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class MasterPermissionGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/dpb-mpg.php', 'dpb-mpg');
    }

    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__.'/../../config/dpb-mpg.php' => config_path('dpb-mpg.php'),
        ], 'dpb-mpg-config');

        $router->prependMiddlewareToGroup('web', EnableMasterPermissionGuard::class);
        if (! $this->app->runningInConsole()) {
            $this->initializeCache();
        }
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'dpb-mpg');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'dpb-mpg');
        $this->registerConsoleCommands();
        $this->registerFilamentPlugin();
    }

    private function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DiscoverProtectedModels::class]);
            $this->commands([RbacSyncCommand::class]);
        }
    }

    private function initializeCache(): void
    {
        Registry::seed(cache()->get('mpg:tables', []));
    }

    private function registerFilamentPlugin(): void
    {
        config()->set('admin-panel.plugins', array_merge(
            [DpbMpgPlugin::class],
            config('admin-panel.plugins', [])
        ));
    }
}
