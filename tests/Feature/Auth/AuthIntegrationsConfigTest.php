<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Auth\AuthIntegrations;
use App\Filament\Pages\Integrations\ManageAuthentication;
use App\Models\User;
use Livewire\Livewire;

it('keeps local auth enabled by default', function (): void {
    expect(AuthIntegrations::localEnabled())->toBeTrue()
        ->and(AuthIntegrations::oidcEnabled())->toBeFalse()
        ->and(AuthIntegrations::ldapEnabled())->toBeFalse();
});

it('requires issuer and client credentials before OIDC is considered enabled', function (): void {
    config([
        'auth_integrations.oidc.enabled' => true,
        'auth_integrations.oidc.issuer' => null,
        'auth_integrations.oidc.client_id' => 'id',
        'auth_integrations.oidc.client_secret' => 'secret',
    ]);

    expect(AuthIntegrations::oidcEnabled())->toBeFalse();

    config(['auth_integrations.oidc.issuer' => 'https://idp.example/realms/app']);

    expect(AuthIntegrations::oidcEnabled())->toBeTrue();
});

it('allows admins to view the authentication integrations page', function (): void {
    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageAuthentication::class)
        ->assertSuccessful()
        ->assertSee('OpenID Connect')
        ->assertSee('LDAP');
});

it('stores external users without a local password', function (): void {
    $user = User::forceCreate([
        'name' => 'SSO User',
        'email' => 'sso@example.com',
        'password' => null,
        'auth_provider' => 'oidc',
        'external_id' => 'sub-1',
        'is_admin' => false,
    ]);

    expect($user->fresh()->password)->toBeNull()
        ->and($user->usesLocalPassword())->toBeFalse();
});
