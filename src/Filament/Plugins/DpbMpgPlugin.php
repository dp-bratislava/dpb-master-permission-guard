<?php

namespace Dpb\MasterPermissionGuard\Filament\Plugins;

use Dpb\MasterPermissionGuard\Filament\Pages\PermissionManagerPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DpbMpgPlugin implements Plugin
{
    public function getId(): string
    {
        return 'dpb-master-permission-guard';
    }

    public function register(
        Panel $panel
    ): void {
        $panel->pages([
            PermissionManagerPage::class
        ]);
    }

    public function boot(
        Panel $panel
    ): void {

    }
}
