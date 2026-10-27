<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Dashboard source of truth for local / OIDC / LDAP auth integrations.
 *
 * When {@see $use_dashboard} is false, env-backed config/auth_integrations.php
 * remains active (bootstrap before first admin login). After the first save
 * from Integrations → Authentication, dashboard settings win.
 */
class AuthIntegrationSettings extends Settings
{
    /**
     * When true, values below override env/config for auth integrations.
     */
    public bool $use_dashboard = false;

    public bool $local_enabled = true;

    public bool $oidc_enabled = false;

    public ?string $oidc_issuer = null;

    public ?string $oidc_client_id = null;

    public ?string $oidc_client_secret = null;

    public ?string $oidc_redirect = null;

    public string $oidc_scopes = 'openid profile email';

    public string $oidc_button_label = 'Sign in with SSO';

    public string $oidc_attr_external_id = 'sub';

    public string $oidc_attr_email = 'email';

    public string $oidc_attr_name = 'name';

    public string $oidc_groups_claim_paths = 'groups,realm_access.roles,resource_access.cachet.roles';

    public bool $oidc_auto_provision = true;

    public bool $oidc_default_is_admin = false;

    public bool $ldap_enabled = false;

    public ?string $ldap_host = null;

    public int $ldap_port = 389;

    public bool $ldap_use_tls = false;

    public bool $ldap_use_ssl = false;

    public int $ldap_timeout = 5;

    public ?string $ldap_base_dn = null;

    public string $ldap_filter = '(uid={username})';

    public ?string $ldap_bind_dn = null;

    public ?string $ldap_bind_password = null;

    public string $ldap_attr_external_id = 'entryUUID';

    public string $ldap_attr_email = 'mail';

    public string $ldap_attr_name = 'cn';

    public string $ldap_attr_username = 'uid';

    public string $ldap_group_attribute = 'memberOf';

    public ?string $ldap_group_filter = null;

    public bool $ldap_auto_provision = true;

    public bool $ldap_default_is_admin = false;

    public static function group(): string
    {
        return 'auth_integrations';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return [
            'oidc_client_secret',
            'ldap_bind_password',
        ];
    }
}
