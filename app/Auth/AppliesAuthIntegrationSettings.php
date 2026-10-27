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

use App\Settings\AuthIntegrationSettings;
use App\Settings\AuthorizationSettings;
use Throwable;

/**
 * Overlay Spatie dashboard auth settings onto config('auth_integrations.*')
 * and sync role maps into AuthorizationSettings when provided.
 */
class AppliesAuthIntegrationSettings
{
    public static function apply(): void
    {
        try {
            $settings = app(AuthIntegrationSettings::class);

            if (! $settings->use_dashboard) {
                return;
            }

            $scopes = array_values(array_filter(array_map(
                'trim',
                explode(' ', (string) $settings->oidc_scopes)
            )));

            $groupPaths = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) $settings->oidc_groups_claim_paths)
            )));

            $redirect = filled($settings->oidc_redirect)
                ? $settings->oidc_redirect
                : rtrim((string) config('app.url'), '/').'/auth/oidc/callback';

            config([
                'auth_integrations.local.enabled' => $settings->local_enabled,
                'auth_integrations.oidc.enabled' => $settings->oidc_enabled,
                'auth_integrations.oidc.issuer' => $settings->oidc_issuer,
                'auth_integrations.oidc.client_id' => $settings->oidc_client_id,
                'auth_integrations.oidc.client_secret' => $settings->oidc_client_secret,
                'auth_integrations.oidc.redirect' => $redirect,
                'auth_integrations.oidc.scopes' => $scopes,
                'auth_integrations.oidc.button_label' => $settings->oidc_button_label,
                'auth_integrations.oidc.attribute_map.external_id' => $settings->oidc_attr_external_id,
                'auth_integrations.oidc.attribute_map.email' => $settings->oidc_attr_email,
                'auth_integrations.oidc.attribute_map.name' => $settings->oidc_attr_name,
                'auth_integrations.oidc.groups_claim_paths' => $groupPaths,
                'auth_integrations.oidc.auto_provision' => $settings->oidc_auto_provision,
                'auth_integrations.oidc.default_is_admin' => $settings->oidc_default_is_admin,
                'auth_integrations.ldap.enabled' => $settings->ldap_enabled,
                'auth_integrations.ldap.host' => $settings->ldap_host,
                'auth_integrations.ldap.port' => $settings->ldap_port,
                'auth_integrations.ldap.use_tls' => $settings->ldap_use_tls,
                'auth_integrations.ldap.use_ssl' => $settings->ldap_use_ssl,
                'auth_integrations.ldap.timeout' => $settings->ldap_timeout,
                'auth_integrations.ldap.base_dn' => $settings->ldap_base_dn,
                'auth_integrations.ldap.filter' => $settings->ldap_filter,
                'auth_integrations.ldap.bind_dn' => $settings->ldap_bind_dn,
                'auth_integrations.ldap.bind_password' => $settings->ldap_bind_password,
                'auth_integrations.ldap.attribute_map.external_id' => $settings->ldap_attr_external_id,
                'auth_integrations.ldap.attribute_map.email' => $settings->ldap_attr_email,
                'auth_integrations.ldap.attribute_map.name' => $settings->ldap_attr_name,
                'auth_integrations.ldap.attribute_map.username' => $settings->ldap_attr_username,
                'auth_integrations.ldap.group_attribute' => $settings->ldap_group_attribute,
                'auth_integrations.ldap.group_filter' => $settings->ldap_group_filter,
                'auth_integrations.ldap.auto_provision' => $settings->ldap_auto_provision,
                'auth_integrations.ldap.default_is_admin' => $settings->ldap_default_is_admin,
            ]);
        } catch (Throwable) {
            // Settings table may not exist yet (fresh install / early boot / tests).
        }
    }

    /**
     * Persist OIDC/LDAP role maps into AuthorizationSettings (shared with the Authorization page).
     *
     * @param  array<string, string>|mixed  $oidcRoleMap
     * @param  array<string, string>|mixed  $ldapRoleMap
     */
    public static function syncRoleMaps(mixed $oidcRoleMap, mixed $ldapRoleMap): void
    {
        try {
            $authorization = app(AuthorizationSettings::class);
        } catch (Throwable) {
            return;
        }

        if (is_array($oidcRoleMap)) {
            $authorization->oidc_role_map = self::normalizeRoleMap($oidcRoleMap);
        }

        if (is_array($ldapRoleMap)) {
            $authorization->ldap_role_map = self::normalizeRoleMap($ldapRoleMap);
        }

        $authorization->save();
    }

    /**
     * @param  array<mixed, mixed>  $map
     * @return array<string, string>
     */
    private static function normalizeRoleMap(array $map): array
    {
        $normalized = [];

        foreach ($map as $external => $role) {
            $external = trim((string) $external);
            $role = trim((string) $role);

            if ($external === '' || $role === '') {
                continue;
            }

            $normalized[$external] = $role;
        }

        return $normalized;
    }
}
