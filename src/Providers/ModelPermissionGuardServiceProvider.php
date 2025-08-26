<?php

namespace Dpb\ModelPermissionGuard\Providers;

use Illuminate\Support\ServiceProvider;

class ModelPermissionGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'dpb-model-permission-guard');
    }
}
