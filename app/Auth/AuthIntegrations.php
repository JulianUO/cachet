<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth;

class AuthIntegrations
{
    public static function localEnabled(): bool
    {
        return (bool) config('auth_integrations.local.enabled', true);
    }

    public static function oidcEnabled(): bool
    {
        return (bool) config('auth_integrations.oidc.enabled', false)
            && filled(config('auth_integrations.oidc.issuer'))
            && filled(config('auth_integrations.oidc.client_id'))
            && filled(config('auth_integrations.oidc.client_secret'));
    }

    public static function oidcConfigured(): bool
    {
        return filled(config('auth_integrations.oidc.issuer'))
            && filled(config('auth_integrations.oidc.client_id'))
            && filled(config('auth_integrations.oidc.client_secret'));
    }

    public static function ldapEnabled(): bool
    {
        return (bool) config('auth_integrations.ldap.enabled', false)
            && filled(config('auth_integrations.ldap.host'))
            && filled(config('auth_integrations.ldap.base_dn'));
    }

    public static function ldapConfigured(): bool
    {
        return filled(config('auth_integrations.ldap.host'))
            && filled(config('auth_integrations.ldap.base_dn'));
    }

    /**
     * Password form is shown when local and/or LDAP auth is available.
     */
    public static function passwordFormEnabled(): bool
    {
        return self::localEnabled() || self::ldapEnabled();
    }
}
