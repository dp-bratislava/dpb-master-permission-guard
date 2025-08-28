<?php

namespace Dpb\MasterPermissionGuard\Filament\Pages;

use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Permission;

class PermissionManagerPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'dpb-mpg::filament.pages.permission-manager-page';

    #[Computed()]
    public function permissions(): array
    {
        $mpgPermissions = Permission::whereLike('name', 'dpb-mpg.%')
            ->get()
            ->toArray();
        $grouppedPermissions = [];
        foreach ($mpgPermissions as $permission) {
            list($mpgPrefix, $tableName, $operation) = explode('.', $permission['name'], 3);
            $grouppedPermissions[$permission['guard_name']][$tableName][$operation] = $permission;
        }



        dd($grouppedPermissions);


        $grouppedPermissions = [];
        foreach ($allPermissions as $permission) {
            list($package, $permissionName) = explode('.', $permission['name'], 2);
            if ($package === 'dpb-mpg') {
                list($tableName, $operation) = explode('.', $permissionName, 2);
                $grouppedPermissions[$permission['guard_name']][$package][$tableName][$operation] = $permission;
            } else {
                $grouppedPermissions[$permission['guard_name']][$package][$permissionName] = $permission;
            }
        }
        return $grouppedPermissions;
    }

    public function form(
        Form $form
    ): Form {
        $permissions = $this->permissions();
        $guards = (new Collection(array_keys($permissions)))
            ->mapWithKeys(fn ($guard) => [$guard => $guard])
            ->toArray();
        return $form
            ->schema([
                Grid::make(6)
                    ->schema([
                        Select::make('guard')
                            ->options($guards)
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.guards'))
                            ->inlineLabel()
                            ->live()
                            ->default(array_key_first($guards) ?? null)
                            ->selectablePlaceholder(false),
                        Select::make('package')
                            ->options(
                                fn (callable $get) => !empty($get('guard'))
                                    ? (new Collection(array_keys($permissions[$get('guard')])))
                                        ->mapWithKeys(fn ($package) => [$package => $package])
                                        ->toArray()
                                    : []
                            )
                            ->live()
                            ->afterStateUpdated(fn ($state) => dd($state))
                            ->inlineLabel()
                            ->default('dpb-mpg')
                            ->selectablePlaceholder(false),
                        Select::make('table')
                            ->options(
                                fn (callable $get) => !empty($get('guard')) && !empty($get('package'))
                                    ? (new Collection(array_keys($permissions[$get('guard')][$get('package')])))
                                        ->mapWithKeys(fn ($table) => [$table => $table])
                                        ->toArray()
                                    : []
                            )
                            ->inlineLabel()
                            ->afterStateUpdated(fn ($state) => dd($state))
                            ->visible(true)
                            ->selectablePlaceholder(false),
                    ])
            ])
            ->fill([
                'guard' => 'web',
                'package' => 'dpb-mpg'
            ]);
    }

    public static function getSlug(): string
    {
        return 'dpb-mpg';
    }

    public static function getNavigationLabel(): string
    {
        return __('dpb-mpg::translations.filament_page.label');
    }

    public function getTitle(): string
    {
        return '';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::getAuthUser()?->hasRole('super-admin') ?? false;
    }

    public static function canAccess(): bool
    {
        return self::getAuthUser()?->hasRole('super-admin') ?? false;
    }

    protected static function getAuthUser(): ?User
    {
        return Auth::user();
    }
}
