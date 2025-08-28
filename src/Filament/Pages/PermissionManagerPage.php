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

    public array $filters = [
        'type' => 'mpg',
        'guard' => 'web',
        'package' => 'dpb-mpg',
        'table' => ''
    ];

    protected static string $view = 'dpb-mpg::filament.pages.permission-manager-page';

    #[Computed()]
    public function permissions(): array
    {
        $permissionsQuery = Permission::query();
        if ($this->filters['type'] === 'mpg') {
            if (empty($this->filters['table'])) {
                $permissionsQuery->whereLike('name', 'dpb-mpg.%');
            } else {
                $permissionsQuery->whereLike('name', 'dpb-mpg.'.$this->filters['table'].'.%');
            }
        } else {
            if (empty($this->filters['package'])) {
                $permissionsQuery->whereNotLike('name', 'dpb-mpg.%');
            } else {
                $permissionsQuery->where('name', 'like', $this->filters['package'].'.%');
            }
        }

        if (!empty($this->filters['guard'])) {
            $permissionsQuery->where('guard_name', $this->filters['guard']);
        }

        return $permissionsQuery->orderBy('id')->get()->toArray();
    }

    public function form(
        Form $form
    ): Form {
        return $form
            ->statePath('filters')
            ->schema([
                Grid::make(6)
                    ->schema([
                        Select::make('type')
                            ->options([
                                'mpg' => 'Práva pre modely',
                                'other' => 'Iné práva'
                            ])
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.type'))
                            ->inlineLabel()
                            ->live()
                            ->selectablePlaceholder(false),
                        Select::make('guard')
                            ->options($this->getAvailableGuards())
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.guards'))
                            ->inlineLabel()
                            ->live()
                            ->selectablePlaceholder(false),
                        Select::make('package')
                            ->options(fn () => $this->getAvailablePackages())
                            ->live()
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.package'))
                            ->inlineLabel()
                            ->visible(fn ($get) => $get('type') !== 'mpg')
                            ->selectablePlaceholder(false),
                        Select::make('table')
                            ->options($this->getAvailableTables())
                            ->label(__('dpb-mpg::translations.filament_page.form.fields.table'))
                            ->inlineLabel()
                            ->live()
                            ->placeholder(__('dpb-mpg::translations.filament_page.form.labels.all'))
                            ->visible(fn ($get) => $get('type') === 'mpg'),
                    ])
            ]);
    }

    private function getAvailableGuards(): array
    {
        return Permission::query()
            ->distinct()
            ->pluck('guard_name')
            ->mapWithKeys(fn ($guard) => [$guard => $guard])
            ->toArray();
    }

    private function getAvailablePackages(): array
    {
        return Permission::query()
            ->pluck('name')
            ->map(fn ($name) => explode('.', $name)[0])
            ->unique()
            ->filter(fn ($packageName) => $packageName !== 'dpb-mpg')
            ->mapWithKeys(fn ($package) => [$package => $package])
            ->toArray();
    }

    private function getAvailableTables(): array
    {
        return Permission::query()
            ->pluck('name')
            ->filter(fn ($name) => str_starts_with($name, 'dpb-mpg.'))
            ->map(fn ($name) => explode('.', $name)[1])
            ->unique()
            ->mapWithKeys(fn ($table) => [$table => $table])
            ->toArray();
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
