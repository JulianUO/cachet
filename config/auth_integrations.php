<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Local password authentication
    |--------------------------------------------------------------------------
    |
    | Keep Filament email/password login available alongside OIDC and LDAP.
    | Disable only when every administrator signs in through another provider.
    |
    */
    'local' => [
        'enabled' => env('AUTH_LOCAL_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenID Connect (OIDC)
    |--------------------------------------------------------------------------
    |
    | Aimed at Keycloak and any IdP that publishes a standard OIDC discovery
    | document at {issuer}/.well-known/openid-configuration.
    |
    | Keycloak issuer example:
    |   https://keycloak.example.com/realms/cachet
    |
    | Callback URL to register with the IdP:
    |   {APP_URL}/auth/oidc/callback
    |
    */
    'oidc' => [
        'enabled' => env('OIDC_ENABLED', false),
        'issuer' => env('OIDC_ISSUER'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect' => env('OIDC_REDIRECT_URI', env('APP_URL').'/auth/oidc/callback'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(' ', (string) env('OIDC_SCOPES', 'openid profile email'))
        ))),
        'button_label' => env('OIDC_BUTTON_LABEL', 'Sign in with SSO'),
        /*
         | Claim → Cachet user field mapping.
         */
        'attribute_map' => [
            'external_id' => env('OIDC_ATTR_EXTERNAL_ID', 'sub'),
            'email' => env('OIDC_ATTR_EMAIL', 'email'),
            'name' => env('OIDC_ATTR_NAME', 'name'),
        ],
        /*
         | Claim paths inspected for groups/roles (Keycloak: groups and/or
         | realm_access.roles). Comma-separated env override supported.
         */
        'groups_claim_paths' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OIDC_GROUPS_CLAIM_PATHS', 'groups,realm_access.roles,resource_access.cachet.roles'))
        ))),
        /*
         | When true, first-time OIDC users are created automatically.
         */
        'auto_provision' => env('OIDC_AUTO_PROVISION', true),
        /*
         | New OIDC users receive is_admin when this is true.
         */
        'default_is_admin' => env('OIDC_DEFAULT_IS_ADMIN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP
    |--------------------------------------------------------------------------
    |
    | Bind + search authentication. Users authenticate with their LDAP
    | credentials on the dashboard login form when local auth fails or is
    | disabled.
    |
    */
    'ldap' => [
        'enabled' => env('LDAP_ENABLED', false),
        'host' => env('LDAP_HOST', '127.0.0.1'),
        'port' => (int) env('LDAP_PORT', 389),
        'use_tls' => (bool) env('LDAP_USE_TLS', false),
        'use_ssl' => (bool) env('LDAP_USE_SSL', false),
        'timeout' => (int) env('LDAP_TIMEOUT', 5),
        'base_dn' => env('LDAP_BASE_DN'),
        'filter' => env('LDAP_FILTER', '(uid={username})'),
        'bind_dn' => env('LDAP_BIND_DN'),
        'bind_password' => env('LDAP_BIND_PASSWORD'),
        'attribute_map' => [
            'external_id' => env('LDAP_ATTR_EXTERNAL_ID', 'entryUUID'),
            'email' => env('LDAP_ATTR_EMAIL', 'mail'),
            'name' => env('LDAP_ATTR_NAME', 'cn'),
            'username' => env('LDAP_ATTR_USERNAME', 'uid'),
        ],
        /*
         | Attribute on the user entry listing group DNs (typically memberOf).
         */
        'group_attribute' => env('LDAP_GROUP_ATTRIBUTE', 'memberOf'),
        /*
         | Optional extra group search filter. Use {dn} for the user DN.
         | Example: (&(objectClass=groupOfNames)(member={dn}))
         */
        'group_filter' => env('LDAP_GROUP_FILTER'),
        'auto_provision' => env('LDAP_AUTO_PROVISION', true),
        'default_is_admin' => env('LDAP_DEFAULT_IS_ADMIN', false),
    ],

];
