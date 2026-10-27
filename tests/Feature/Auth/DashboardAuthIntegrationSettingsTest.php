<?php

use App\Auth\AppliesAuthIntegrationSettings;
use App\Auth\AuthIntegrations;
use App\Filament\Pages\Integrations\ManageAuthentication;
use App\Models\User;
use App\Settings\AuthIntegrationSettings;
use App\Settings\AuthorizationSettings;
use Livewire\Livewire;

it('keeps env as source of truth until the dashboard is saved', function (): void {
    config([
        'auth_integrations.oidc.enabled' => true,
        'auth_integrations.oidc.issuer' => 'https://idp.example/realms/app',
        'auth_integrations.oidc.client_id' => 'env-client',
        'auth_integrations.oidc.client_secret' => 'env-secret',
    ]);

    $settings = app(AuthIntegrationSettings::class);
    $settings->use_dashboard = false;
    $settings->save();

    AppliesAuthIntegrationSettings::apply();

    expect(AuthIntegrations::oidcEnabled())->toBeTrue()
        ->and(config('auth_integrations.oidc.client_id'))->toBe('env-client');
});

it('lets dashboard settings override env after save', function (): void {
    config([
        'auth_integrations.oidc.enabled' => false,
        'auth_integrations.oidc.issuer' => null,
        'auth_integrations.oidc.client_id' => null,
        'auth_integrations.oidc.client_secret' => null,
    ]);

    $settings = app(AuthIntegrationSettings::class);
    $settings->use_dashboard = true;
    $settings->local_enabled = true;
    $settings->oidc_enabled = true;
    $settings->oidc_issuer = 'https://keycloak.example/realms/cachet';
    $settings->oidc_client_id = 'dashboard-client';
    $settings->oidc_client_secret = 'dashboard-secret';
    $settings->oidc_scopes = 'openid profile email';
    $settings->oidc_groups_claim_paths = 'groups,realm_access.roles';
    $settings->save();

    AppliesAuthIntegrationSettings::apply();

    expect(AuthIntegrations::oidcEnabled())->toBeTrue()
        ->and(config('auth_integrations.oidc.client_id'))->toBe('dashboard-client')
        ->and(config('auth_integrations.oidc.client_secret'))->toBe('dashboard-secret')
        ->and(config('auth_integrations.oidc.issuer'))->toBe('https://keycloak.example/realms/cachet');
});

it('allows admins to save authentication settings from the integrations page', function (): void {
    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'auth-admin@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageAuthentication::class)
        ->fillForm([
            'local_enabled' => true,
            'oidc_enabled' => true,
            'oidc_issuer' => 'https://keycloak.example/realms/cachet',
            'oidc_client_id' => 'cachet',
            'oidc_client_secret' => 'super-secret',
            'oidc_redirect' => '',
            'oidc_scopes' => 'openid profile email',
            'oidc_button_label' => 'Sign in with SSO',
            'oidc_attr_external_id' => 'sub',
            'oidc_attr_email' => 'email',
            'oidc_attr_name' => 'name',
            'oidc_groups_claim_paths' => 'groups,realm_access.roles',
            'oidc_auto_provision' => true,
            'oidc_default_is_admin' => false,
            'oidc_role_map' => ['cachet-devops' => 'devops'],
            'ldap_enabled' => true,
            'ldap_host' => 'ldap.example.com',
            'ldap_port' => 389,
            'ldap_use_tls' => true,
            'ldap_use_ssl' => false,
            'ldap_timeout' => 5,
            'ldap_base_dn' => 'ou=people,dc=example,dc=com',
            'ldap_filter' => '(uid={username})',
            'ldap_bind_dn' => 'cn=readonly,dc=example,dc=com',
            'ldap_bind_password' => 'bind-secret',
            'ldap_attr_external_id' => 'entryUUID',
            'ldap_attr_email' => 'mail',
            'ldap_attr_name' => 'cn',
            'ldap_attr_username' => 'uid',
            'ldap_group_attribute' => 'memberOf',
            'ldap_group_filter' => '',
            'ldap_auto_provision' => true,
            'ldap_default_is_admin' => false,
            'ldap_role_map' => ['cachet-it-support' => 'it_support'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AuthIntegrationSettings::class)->refresh();

    expect($settings->use_dashboard)->toBeTrue()
        ->and($settings->oidc_enabled)->toBeTrue()
        ->and($settings->oidc_issuer)->toBe('https://keycloak.example/realms/cachet')
        ->and($settings->oidc_client_secret)->toBe('super-secret')
        ->and($settings->ldap_enabled)->toBeTrue()
        ->and($settings->ldap_host)->toBe('ldap.example.com')
        ->and($settings->ldap_bind_password)->toBe('bind-secret')
        ->and(AuthIntegrations::oidcEnabled())->toBeTrue()
        ->and(AuthIntegrations::ldapEnabled())->toBeTrue();

    $authorization = app(AuthorizationSettings::class)->refresh();

    expect($authorization->oidc_role_map)->toMatchArray(['cachet-devops' => 'devops'])
        ->and($authorization->ldap_role_map)->toMatchArray(['cachet-it-support' => 'it_support']);
});

it('keeps existing auth secrets when secret fields are left blank', function (): void {
    $settings = app(AuthIntegrationSettings::class);
    $settings->use_dashboard = true;
    $settings->local_enabled = true;
    $settings->oidc_enabled = true;
    $settings->oidc_issuer = 'https://keycloak.example/realms/cachet';
    $settings->oidc_client_id = 'cachet';
    $settings->oidc_client_secret = 'keep-me';
    $settings->oidc_scopes = 'openid profile email';
    $settings->oidc_groups_claim_paths = 'groups';
    $settings->ldap_enabled = true;
    $settings->ldap_host = 'ldap.example.com';
    $settings->ldap_base_dn = 'ou=people,dc=example,dc=com';
    $settings->ldap_bind_password = 'keep-bind';
    $settings->save();
    AppliesAuthIntegrationSettings::apply();

    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'keep-auth@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageAuthentication::class)
        ->fillForm([
            'local_enabled' => true,
            'oidc_enabled' => true,
            'oidc_issuer' => 'https://keycloak.example/realms/cachet',
            'oidc_client_id' => 'cachet',
            'oidc_client_secret' => '',
            'oidc_scopes' => 'openid profile email',
            'oidc_button_label' => 'Sign in with SSO',
            'oidc_attr_external_id' => 'sub',
            'oidc_attr_email' => 'email',
            'oidc_attr_name' => 'name',
            'oidc_groups_claim_paths' => 'groups',
            'oidc_auto_provision' => true,
            'oidc_default_is_admin' => false,
            'oidc_role_map' => [],
            'ldap_enabled' => true,
            'ldap_host' => 'ldap.example.com',
            'ldap_port' => 389,
            'ldap_use_tls' => false,
            'ldap_use_ssl' => false,
            'ldap_timeout' => 5,
            'ldap_base_dn' => 'ou=people,dc=example,dc=com',
            'ldap_filter' => '(uid={username})',
            'ldap_bind_dn' => '',
            'ldap_bind_password' => '',
            'ldap_attr_external_id' => 'entryUUID',
            'ldap_attr_email' => 'mail',
            'ldap_attr_name' => 'cn',
            'ldap_attr_username' => 'uid',
            'ldap_group_attribute' => 'memberOf',
            'ldap_auto_provision' => true,
            'ldap_default_is_admin' => false,
            'ldap_role_map' => [],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AuthIntegrationSettings::class)->refresh();

    expect($settings->oidc_client_secret)->toBe('keep-me')
        ->and($settings->ldap_bind_password)->toBe('keep-bind');
});

it('shows editable authentication fields without revealing secrets', function (): void {
    $settings = app(AuthIntegrationSettings::class);
    $settings->use_dashboard = true;
    $settings->oidc_enabled = true;
    $settings->oidc_issuer = 'https://keycloak.example/realms/cachet';
    $settings->oidc_client_id = 'cachet';
    $settings->oidc_client_secret = 'never-show-this';
    $settings->ldap_enabled = true;
    $settings->ldap_host = 'ldap.example.com';
    $settings->ldap_base_dn = 'ou=people,dc=example,dc=com';
    $settings->ldap_bind_password = 'never-show-bind';
    $settings->save();
    AppliesAuthIntegrationSettings::apply();

    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'view-auth@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageAuthentication::class)
        ->assertSuccessful()
        ->assertSee('Enable OIDC')
        ->assertSee('Client secret')
        ->assertSee('Enable LDAP')
        ->assertSee('Bind password')
        ->assertSee('OIDC group → Cachet role map')
        ->assertSee('LDAP group → Cachet role map')
        ->assertDontSee('never-show-this')
        ->assertDontSee('never-show-bind')
        ->assertSee('Dashboard (wins over env)');
});
