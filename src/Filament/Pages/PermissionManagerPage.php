<?php

namespace Dpb\MasterPermissionGuard\Filament\Pages;

use Dpb\MasterPermissionGuard\Services\PermissionGuardService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Pages\Page;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagerPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public array $filters = [
        'type' => 'tables',
        'guard' => 'web',
    ];

    protected string $view = 'dpb-mpg::filament.pages.permission-manager-page';

    public function mount(): void
    {
        PermissionGuardService::findRoles();
    }

    #[Computed()]
    public function permissions(): array
    {
        return match($this->filters['type']) {
            'tables' => PermissionGuardService::findTablePermissions()->toArray(),
            'pages' => PermissionGuardService::findPagePermissions()->toArray(),
            'components' => PermissionGuardService::findComponentPermissions()->toArray(),
            'other' => PermissionGuardService::findOtherPermissions()->toArray(),
            default => []
        };
    }

    public function manageAssignedRolesAction(): Action
    {
        return Action::make('manageAssignedRolesAction')
            ->label(fn (array $arguments) => count($this->getPermissionRoles($arguments['id'])))
            ->tooltip(fn ($arguments) => __(
                'dpb-mpg::translations.filament_page.actions.manage_assigned_roles.label',
                ['count' => count($arguments['roles'])]
            ))
            ->modalHeading(__('dpb-mpg::translations.filament_page.actions.manage_assigned_roles.modal_heading'))
            ->form([
                Select::make('roles')
                    ->label(__('dpb-mpg::translations.filament_page.actions.manage_assigned_roles.roles_form_field'))
                    ->multiple()
                    ->options(fn () => Role::query()
                        ->pluck('name', 'id')
                        ->toArray())
                    ->preload(),
            ])
            ->fillForm(
                fn (array $arguments) => [
                    'roles' => Permission::with('roles')
                        ->find($arguments['id'])
                        ?->roles
                        ->pluck('id')
                        ->toArray()
                ]
            )
            ->action(
                function (
                    array $data,
                    array $arguments
                ) {
                    Permission::find($arguments['id'])
                        ?->syncRoles(
                            ...Role::whereIn('id', $data['roles'])
                                ->get()
                                ->pluck('name')
                                ->toArray()
                        );
                    Notification::make()
                        ->title(__('dpb-mpg::translations.filament_page.actions.manage_assigned_roles.notifications.success'))
                        ->success()
                        ->send();
                }
            )
            ->color('primary')
            ->icon('heroicon-o-pencil');
    }

    private function getPermissionRoles(
        int $permissionId
    ): array {
        return Role::whereHas('permissions', function ($q) use ($permissionId) {
            $q->where('id', $permissionId);
        })
        ->with('permissions')
        ->get()
        ->toArray();
    }

    public function form(Schema $schema): Schema {
        return $schema
            ->statePath('filters')
            ->schema([
                Grid::make(6)
                    ->schema([
                        Select::make('type')
                            ->options([
                                'tables' => 'Modely',
                                'pages' => 'Stránky',
                                'components' => 'Komponenty',
                                'other' => 'Iné práva'
                            ])
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.type'))
                            ->inlineLabel()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                switch ($state) {
                                    case 'tables':
                                        $this->filters['package'] = '';
                                        break;
                                    default:
                                        $this->filters['table'] = '';
                                        break;
                                }
                            })
                            ->selectablePlaceholder(false),
                    ])
            ]);
    }

    public static function getSlug(?Panel $panel = null): string
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

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user?->hasRole('super-admin') ?? false;
    }
}
