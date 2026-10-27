<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Filament\Pages\Integrations;

use App\Auth\AppliesAuthIntegrationSettings;
use App\Auth\AuthIntegrations;
use App\Auth\Permission;
use App\Auth\PermissionMatrix;
use App\Models\User;
use App\Settings\AuthIntegrationSettings;
use App\Settings\AuthorizationSettings;
use Cachet\Filament\Pages\Settings\SettingsPage;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Editable authentication integrations (local, OIDC/Keycloak, LDAP).
 * Dashboard is source of truth after the first save; env is bootstrap only.
 */
class ManageAuthentication extends SettingsPage
{
    protected static string $settings = AuthIntegrationSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected string $view = 'filament.pages.integrations.manage-authentication';

    public static function getNavigationGroup(): ?string
    {
        return __('cachet::navigation.integrations.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('Authentication');
    }

    public function getTitle(): string
    {
        return __('Authentication integrations');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->isAdmin() || $user->hasPermission(Permission::AuthIntegrationsManage));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Dashboard as source of truth'))
                    ->description(__('Configure authentication here. Environment variables are optional bootstrap only until you save — after save, these dashboard values win.'))
                    ->schema([
                        Toggle::make('local_enabled')
                            ->label(__('Enable local email/password login'))
                            ->helperText(__('Keep enabled unless every administrator signs in through OIDC or LDAP.')),
                    ]),

                Section::make(__('OpenID Connect (OIDC / Keycloak)'))
                    ->description(__('Standard OIDC discovery at {issuer}/.well-known/openid-configuration. Register the callback URL with your IdP.'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('oidc_enabled')
                            ->label(__('Enable OIDC'))
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('oidc_issuer')
                            ->label(__('Issuer'))
                            ->placeholder('https://keycloak.example.com/realms/cachet')
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        TextInput::make('oidc_client_id')
                            ->label(__('Client ID'))
                            ->maxLength(255)
                            ->autocomplete(false),
                        TextInput::make('oidc_client_secret')
                            ->label(__('Client secret'))
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(2048)
                            ->placeholder(fn (): string => $this->oidcSecretConfigured()
                                ? __('Leave blank to keep the current client secret')
                                : '')
                            ->helperText(fn (): string => $this->oidcSecretConfigured()
                                ? __('A client secret is already saved. Enter a new value to replace it.')
                                : __('Stored encrypted. Never re-shown or logged.')),
                        TextInput::make('oidc_redirect')
                            ->label(__('Callback / redirect URI'))
                            ->helperText(__('Defaults to {APP_URL}/auth/oidc/callback when blank.'))
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        TextInput::make('oidc_scopes')
                            ->label(__('Scopes'))
                            ->helperText(__('Space-separated, e.g. openid profile email'))
                            ->maxLength(255),
                        TextInput::make('oidc_button_label')
                            ->label(__('Login button label'))
                            ->maxLength(120),
                        TextInput::make('oidc_attr_external_id')
                            ->label(__('External ID claim'))
                            ->maxLength(120),
                        TextInput::make('oidc_attr_email')
                            ->label(__('Email claim'))
                            ->maxLength(120),
                        TextInput::make('oidc_attr_name')
                            ->label(__('Name claim'))
                            ->maxLength(120),
                        TextInput::make('oidc_groups_claim_paths')
                            ->label(__('Groups claim paths'))
                            ->helperText(__('Comma-separated paths, e.g. groups,realm_access.roles'))
                            ->maxLength(512)
                            ->columnSpanFull(),
                        Toggle::make('oidc_auto_provision')
                            ->label(__('Auto-provision users on first login')),
                        Toggle::make('oidc_default_is_admin')
                            ->label(__('New OIDC users are admins')),
                        KeyValue::make('oidc_role_map')
                            ->label(__('OIDC group → Cachet role map'))
                            ->keyLabel(__('External group / role'))
                            ->valueLabel(__('Cachet role'))
                            ->helperText(__('Also editable under Settings → Authorization. Highest precedence wins (devops > it_support > …).'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('LDAP'))
                    ->description(__('Bind + search authentication. Users sign in with LDAP credentials on the dashboard login form when local auth fails or is disabled.'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('ldap_enabled')
                            ->label(__('Enable LDAP'))
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('ldap_host')
                            ->label(__('Host'))
                            ->maxLength(255),
                        TextInput::make('ldap_port')
                            ->label(__('Port'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535),
                        Toggle::make('ldap_use_tls')
                            ->label(__('Use STARTTLS')),
                        Toggle::make('ldap_use_ssl')
                            ->label(__('Use LDAPS (SSL)')),
                        TextInput::make('ldap_timeout')
                            ->label(__('Timeout (seconds)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120),
                        TextInput::make('ldap_base_dn')
                            ->label(__('Base DN'))
                            ->maxLength(512)
                            ->columnSpanFull(),
                        TextInput::make('ldap_filter')
                            ->label(__('User filter'))
                            ->helperText(__('Use {username} as a placeholder.'))
                            ->maxLength(512)
                            ->columnSpanFull(),
                        TextInput::make('ldap_bind_dn')
                            ->label(__('Bind DN'))
                            ->maxLength(512)
                            ->columnSpanFull(),
                        TextInput::make('ldap_bind_password')
                            ->label(__('Bind password'))
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(2048)
                            ->placeholder(fn (): string => $this->ldapSecretConfigured()
                                ? __('Leave blank to keep the current bind password')
                                : '')
                            ->helperText(fn (): string => $this->ldapSecretConfigured()
                                ? __('A bind password is already saved. Enter a new value to replace it.')
                                : __('Stored encrypted. Never re-shown or logged.'))
                            ->columnSpanFull(),
                        TextInput::make('ldap_attr_external_id')
                            ->label(__('External ID attribute'))
                            ->maxLength(120),
                        TextInput::make('ldap_attr_email')
                            ->label(__('Email attribute'))
                            ->maxLength(120),
                        TextInput::make('ldap_attr_name')
                            ->label(__('Name attribute'))
                            ->maxLength(120),
                        TextInput::make('ldap_attr_username')
                            ->label(__('Username attribute'))
                            ->maxLength(120),
                        TextInput::make('ldap_group_attribute')
                            ->label(__('Group attribute'))
                            ->maxLength(120),
                        TextInput::make('ldap_group_filter')
                            ->label(__('Optional group search filter'))
                            ->helperText(__('Use {dn} for the user DN.'))
                            ->maxLength(512)
                            ->columnSpanFull(),
                        Toggle::make('ldap_auto_provision')
                            ->label(__('Auto-provision users on first login')),
                        Toggle::make('ldap_default_is_admin')
                            ->label(__('New LDAP users are admins')),
                        KeyValue::make('ldap_role_map')
                            ->label(__('LDAP group → Cachet role map'))
                            ->keyLabel(__('Group CN / DN'))
                            ->valueLabel(__('Cachet role'))
                            ->helperText(__('Also editable under Settings → Authorization.'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! ($data['use_dashboard'] ?? false)) {
            $data = array_merge($data, $this->bootstrapFromConfig());
        }

        $data['oidc_client_secret'] = '';
        $data['ldap_bind_password'] = '';

        $authorization = app(AuthorizationSettings::class);
        $matrix = app(PermissionMatrix::class);

        $data['oidc_role_map'] = is_array($authorization->oidc_role_map) && $authorization->oidc_role_map !== []
            ? $authorization->oidc_role_map
            : $matrix->oidcRoleMap();

        $data['ldap_role_map'] = is_array($authorization->ldap_role_map) && $authorization->ldap_role_map !== []
            ? $authorization->ldap_role_map
            : $matrix->ldapRoleMap();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = app(AuthIntegrationSettings::class);

        $oidcRoleMap = $data['oidc_role_map'] ?? [];
        $ldapRoleMap = $data['ldap_role_map'] ?? [];
        unset($data['oidc_role_map'], $data['ldap_role_map']);

        AppliesAuthIntegrationSettings::syncRoleMaps($oidcRoleMap, $ldapRoleMap);

        if (! filled($data['oidc_client_secret'] ?? null)) {
            $data['oidc_client_secret'] = $settings->use_dashboard
                ? $settings->oidc_client_secret
                : config('auth_integrations.oidc.client_secret');
        }

        if (! filled($data['ldap_bind_password'] ?? null)) {
            $data['ldap_bind_password'] = $settings->use_dashboard
                ? $settings->ldap_bind_password
                : config('auth_integrations.ldap.bind_password');
        }

        $data['use_dashboard'] = true;
        $data['oidc_client_secret'] = filled($data['oidc_client_secret'] ?? null)
            ? trim((string) $data['oidc_client_secret'])
            : null;
        $data['ldap_bind_password'] = filled($data['ldap_bind_password'] ?? null)
            ? trim((string) $data['ldap_bind_password'])
            : null;
        $data['ldap_port'] = (int) ($data['ldap_port'] ?? 389);
        $data['ldap_timeout'] = (int) ($data['ldap_timeout'] ?? 5);

        foreach ([
            'oidc_issuer', 'oidc_client_id', 'oidc_redirect', 'ldap_host', 'ldap_base_dn',
            'ldap_bind_dn', 'ldap_group_filter',
        ] as $nullable) {
            $data[$nullable] = filled($data[$nullable] ?? null) ? trim((string) $data[$nullable]) : null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        AppliesAuthIntegrationSettings::apply();
    }

    /**
     * Prefill the form from env-backed config before the first dashboard save.
     *
     * @return array<string, mixed>
     */
    private function bootstrapFromConfig(): array
    {
        return [
            'local_enabled' => (bool) config('auth_integrations.local.enabled', true),
            'oidc_enabled' => (bool) config('auth_integrations.oidc.enabled', false),
            'oidc_issuer' => config('auth_integrations.oidc.issuer'),
            'oidc_client_id' => config('auth_integrations.oidc.client_id'),
            'oidc_redirect' => config('auth_integrations.oidc.redirect'),
            'oidc_scopes' => implode(' ', config('auth_integrations.oidc.scopes', [])),
            'oidc_button_label' => (string) config('auth_integrations.oidc.button_label', 'Sign in with SSO'),
            'oidc_attr_external_id' => (string) config('auth_integrations.oidc.attribute_map.external_id', 'sub'),
            'oidc_attr_email' => (string) config('auth_integrations.oidc.attribute_map.email', 'email'),
            'oidc_attr_name' => (string) config('auth_integrations.oidc.attribute_map.name', 'name'),
            'oidc_groups_claim_paths' => implode(',', config('auth_integrations.oidc.groups_claim_paths', [])),
            'oidc_auto_provision' => (bool) config('auth_integrations.oidc.auto_provision', true),
            'oidc_default_is_admin' => (bool) config('auth_integrations.oidc.default_is_admin', false),
            'ldap_enabled' => (bool) config('auth_integrations.ldap.enabled', false),
            'ldap_host' => config('auth_integrations.ldap.host'),
            'ldap_port' => (int) config('auth_integrations.ldap.port', 389),
            'ldap_use_tls' => (bool) config('auth_integrations.ldap.use_tls', false),
            'ldap_use_ssl' => (bool) config('auth_integrations.ldap.use_ssl', false),
            'ldap_timeout' => (int) config('auth_integrations.ldap.timeout', 5),
            'ldap_base_dn' => config('auth_integrations.ldap.base_dn'),
            'ldap_filter' => (string) config('auth_integrations.ldap.filter', '(uid={username})'),
            'ldap_bind_dn' => config('auth_integrations.ldap.bind_dn'),
            'ldap_attr_external_id' => (string) config('auth_integrations.ldap.attribute_map.external_id', 'entryUUID'),
            'ldap_attr_email' => (string) config('auth_integrations.ldap.attribute_map.email', 'mail'),
            'ldap_attr_name' => (string) config('auth_integrations.ldap.attribute_map.name', 'cn'),
            'ldap_attr_username' => (string) config('auth_integrations.ldap.attribute_map.username', 'uid'),
            'ldap_group_attribute' => (string) config('auth_integrations.ldap.group_attribute', 'memberOf'),
            'ldap_group_filter' => config('auth_integrations.ldap.group_filter'),
            'ldap_auto_provision' => (bool) config('auth_integrations.ldap.auto_provision', true),
            'ldap_default_is_admin' => (bool) config('auth_integrations.ldap.default_is_admin', false),
        ];
    }

    private function oidcSecretConfigured(): bool
    {
        $settings = app(AuthIntegrationSettings::class);

        if ($settings->use_dashboard) {
            return filled($settings->oidc_client_secret);
        }

        return filled(config('auth_integrations.oidc.client_secret'));
    }

    private function ldapSecretConfigured(): bool
    {
        $settings = app(AuthIntegrationSettings::class);

        if ($settings->use_dashboard) {
            return filled($settings->ldap_bind_password);
        }

        return filled(config('auth_integrations.ldap.bind_password'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $settings = app(AuthIntegrationSettings::class);

        return [
            'source' => $settings->use_dashboard ? 'dashboard' : 'env',
            'local' => [
                'enabled' => AuthIntegrations::localEnabled(),
            ],
            'oidc' => [
                'enabled' => AuthIntegrations::oidcEnabled(),
                'configured' => AuthIntegrations::oidcConfigured(),
                'secret_configured' => $this->oidcSecretConfigured(),
                'issuer' => (string) config('auth_integrations.oidc.issuer'),
                'client_id' => (string) config('auth_integrations.oidc.client_id'),
                'redirect' => (string) config('auth_integrations.oidc.redirect'),
                'scopes' => implode(' ', config('auth_integrations.oidc.scopes', [])),
            ],
            'ldap' => [
                'enabled' => AuthIntegrations::ldapEnabled(),
                'configured' => AuthIntegrations::ldapConfigured(),
                'secret_configured' => $this->ldapSecretConfigured(),
                'host' => (string) config('auth_integrations.ldap.host'),
                'port' => (string) config('auth_integrations.ldap.port'),
                'base_dn' => (string) config('auth_integrations.ldap.base_dn'),
                'filter' => (string) config('auth_integrations.ldap.filter'),
                'use_tls' => (bool) config('auth_integrations.ldap.use_tls'),
                'use_ssl' => (bool) config('auth_integrations.ldap.use_ssl'),
            ],
        ];
    }
}
