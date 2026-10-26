<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Auth\Ldap\LdapAuthenticator;
use App\Filament\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    config([
        'auth_integrations.local.enabled' => true,
        'auth_integrations.ldap.enabled' => true,
        'auth_integrations.ldap.host' => 'ldap.test',
        'auth_integrations.ldap.base_dn' => 'ou=people,dc=example,dc=com',
        'auth_integrations.ldap.auto_provision' => true,
        'auth_integrations.ldap.default_is_admin' => false,
    ]);
});

it('authenticates via LDAP when local credentials fail', function (): void {
    $this->mock(LdapAuthenticator::class, function ($mock): void {
        $mock->shouldReceive('attempt')
            ->once()
            ->with('ldap.user', 'ldap-password')
            ->andReturn([
                'external_id' => 'ldap-1',
                'email' => 'ldap@example.com',
                'name' => 'LDAP User',
                'username' => 'ldap.user',
            ]);
    });

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'ldap.user',
            'password' => 'ldap-password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ldap@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth_provider)->toBe('ldap')
        ->and($user->external_id)->toBe('ldap-1')
        ->and($user->password)->toBeNull();
});

it('still allows local password login when LDAP is enabled', function (): void {
    $user = User::forceCreate([
        'name' => 'Local Admin',
        'email' => 'local@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->mock(LdapAuthenticator::class, function ($mock): void {
        $mock->shouldReceive('attempt')->never();
    });

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'local@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid LDAP credentials', function (): void {
    $this->mock(LdapAuthenticator::class, function ($mock): void {
        $mock->shouldReceive('attempt')
            ->once()
            ->andReturn(null);
    });

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'nobody',
            'password' => 'wrong',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});
