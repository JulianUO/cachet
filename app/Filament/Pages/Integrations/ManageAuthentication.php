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

use App\Auth\AuthIntegrations;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Admin view of authentication integration status.
 * Enable/disable and credentials are configured via environment variables
 * (see config/auth_integrations.php and .env.example).
 */
class ManageAuthentication extends Page
{
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

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return [
            'local' => [
                'enabled' => AuthIntegrations::localEnabled(),
            ],
            'oidc' => [
                'enabled' => AuthIntegrations::oidcEnabled(),
                'configured' => AuthIntegrations::oidcConfigured(),
                'issuer' => (string) config('auth_integrations.oidc.issuer'),
                'client_id' => (string) config('auth_integrations.oidc.client_id'),
                'redirect' => (string) config('auth_integrations.oidc.redirect'),
                'scopes' => implode(' ', config('auth_integrations.oidc.scopes', [])),
            ],
            'ldap' => [
                'enabled' => AuthIntegrations::ldapEnabled(),
                'configured' => AuthIntegrations::ldapConfigured(),
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
