<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Filament\Pages\Settings;

use App\Auth\Permission;
use App\Auth\PermissionMatrix;
use App\Auth\Role;
use App\Models\User;
use App\Settings\AuthorizationSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * @property Schema $form
 */
class ManageAuthorization extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected string $view = 'filament.pages.settings.manage-authorization';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('cachet::navigation.settings.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('Authorization');
    }

    public function getTitle(): string
    {
        return __('Permission matrix');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasPermission(Permission::AuthorizationManage);
    }

    public function mount(): void
    {
        $matrix = app(PermissionMatrix::class)->matrix();
        $settings = app(AuthorizationSettings::class);

        $rolePermissions = [];

        foreach (Role::cases() as $role) {
            $rolePermissions[$role->value] = $matrix[$role->value] ?? [];
        }

        $this->form->fill([
            'role_permissions' => $rolePermissions,
            'oidc_role_map' => $settings->oidc_role_map !== []
                ? $settings->oidc_role_map
                : config('authorization.oidc_role_map', []),
            'ldap_role_map' => $settings->ldap_role_map !== []
                ? $settings->ldap_role_map
                : config('authorization.ldap_role_map', []),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $permissionOptions = Permission::options();
        $roleSections = [];

        foreach (Role::cases() as $role) {
            $roleSections[] = CheckboxList::make("role_permissions.{$role->value}")
                ->label($role->label())
                ->options($permissionOptions)
                ->columns(2)
                ->bulkToggleable()
                ->gridDirection('row');
        }

        return $schema
            ->components([
                Section::make(__('Role permissions'))
                    ->description(__('Choose what each Cachet role can do. Admins (is_admin) always bypass this matrix.'))
                    ->schema($roleSections),

                Section::make(__('OIDC / Keycloak group mapping'))
                    ->description(__('Map IdP group or realm-role names (from OIDC groups claims) to Cachet roles. Highest privilege wins when multiple match.'))
                    ->schema([
                        KeyValue::make('oidc_role_map')
                            ->label(__('OIDC group → Cachet role'))
                            ->keyLabel(__('IdP group / role'))
                            ->valueLabel(__('Cachet role'))
                            ->editableKeys()
                            ->editableValues()
                            ->addActionLabel(__('Add mapping')),
                    ]),

                Section::make(__('LDAP group mapping'))
                    ->description(__('Map LDAP group CNs or DNs (from memberOf / group search) to Cachet roles.'))
                    ->schema([
                        KeyValue::make('ldap_role_map')
                            ->label(__('LDAP group → Cachet role'))
                            ->keyLabel(__('LDAP group CN or DN'))
                            ->valueLabel(__('Cachet role'))
                            ->editableKeys()
                            ->editableValues()
                            ->addActionLabel(__('Add mapping')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->orderBy('name'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('auth_provider')
                    ->label(__('Provider'))
                    ->placeholder('local')
                    ->badge(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->formatStateUsing(fn (?string $state): string => Role::tryFrom((string) $state)?->label() ?? ($state ?: '—')),
                TextColumn::make('is_admin')
                    ->label(__('Admin'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('Yes') : __('No')),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('assignRole')
                    ->label(__('Assign role'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->visible(fn (User $record): bool => ! $record->isAdmin())
                    ->form([
                        Select::make('role')
                            ->label(__('Role'))
                            ->options(Role::options())
                            ->required(),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->role ?: config('authorization.default_role'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update(['role' => $data['role']]);

                        Notification::make()
                            ->title(__('Role updated'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save matrix'))
                ->action(fn () => $this->save()),
            Action::make('resetDefaults')
                ->label(__('Reset to defaults'))
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->resetDefaults()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = app(AuthorizationSettings::class);

        $matrix = [];

        foreach (Role::cases() as $role) {
            $matrix[$role->value] = array_values($state['role_permissions'][$role->value] ?? []);
        }

        $settings->matrix = $matrix;
        $settings->oidc_role_map = $this->normalizeMap($state['oidc_role_map'] ?? []);
        $settings->ldap_role_map = $this->normalizeMap($state['ldap_role_map'] ?? []);
        $settings->save();

        Notification::make()
            ->title(__('Authorization settings saved'))
            ->success()
            ->send();
    }

    public function resetDefaults(): void
    {
        $settings = app(AuthorizationSettings::class);
        $settings->matrix = [];
        $settings->oidc_role_map = [];
        $settings->ldap_role_map = [];
        $settings->save();

        $this->mount();

        Notification::make()
            ->title(__('Restored config defaults'))
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, string>
     */
    private function normalizeMap(array $map): array
    {
        $normalized = [];

        foreach ($map as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);

            if ($key === '' || $value === '') {
                continue;
            }

            if (Role::tryFrom($value) === null) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
