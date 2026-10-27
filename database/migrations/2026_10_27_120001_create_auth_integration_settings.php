<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        rescue(fn () => $this->migrator->add('auth_integrations.use_dashboard', false));
        rescue(fn () => $this->migrator->add('auth_integrations.local_enabled', true));

        rescue(fn () => $this->migrator->add('auth_integrations.oidc_enabled', false));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_issuer', null));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_client_id', null));
        rescue(fn () => $this->migrator->addEncrypted('auth_integrations.oidc_client_secret', null));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_redirect', null));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_scopes', 'openid profile email'));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_button_label', 'Sign in with SSO'));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_attr_external_id', 'sub'));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_attr_email', 'email'));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_attr_name', 'name'));
        rescue(fn () => $this->migrator->add(
            'auth_integrations.oidc_groups_claim_paths',
            'groups,realm_access.roles,resource_access.cachet.roles'
        ));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_auto_provision', true));
        rescue(fn () => $this->migrator->add('auth_integrations.oidc_default_is_admin', false));

        rescue(fn () => $this->migrator->add('auth_integrations.ldap_enabled', false));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_host', null));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_port', 389));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_use_tls', false));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_use_ssl', false));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_timeout', 5));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_base_dn', null));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_filter', '(uid={username})'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_bind_dn', null));
        rescue(fn () => $this->migrator->addEncrypted('auth_integrations.ldap_bind_password', null));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_attr_external_id', 'entryUUID'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_attr_email', 'mail'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_attr_name', 'cn'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_attr_username', 'uid'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_group_attribute', 'memberOf'));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_group_filter', null));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_auto_provision', true));
        rescue(fn () => $this->migrator->add('auth_integrations.ldap_default_is_admin', false));
    }
};
