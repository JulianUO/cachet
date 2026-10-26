<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'auth_integrations.oidc.enabled' => true,
        'auth_integrations.oidc.issuer' => 'https://keycloak.test/realms/cachet',
        'auth_integrations.oidc.client_id' => 'cachet',
        'auth_integrations.oidc.client_secret' => 'secret',
        'auth_integrations.oidc.redirect' => 'http://cachet.test/auth/oidc/callback',
        'auth_integrations.oidc.scopes' => ['openid', 'profile', 'email'],
        'auth_integrations.oidc.auto_provision' => true,
        'auth_integrations.oidc.default_is_admin' => true,
    ]);
});

it('redirects to the OIDC authorization endpoint', function (): void {
    Http::fake([
        'https://keycloak.test/realms/cachet/.well-known/openid-configuration' => Http::response([
            'authorization_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/auth',
            'token_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/token',
            'userinfo_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/userinfo',
        ]),
    ]);

    $response = $this->get(route('auth.oidc.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith('https://keycloak.test/realms/cachet/protocol/openid-connect/auth')
        ->toContain('client_id=cachet')
        ->toContain('response_type=code');

    expect(session('oidc.state'))->not->toBeEmpty();
});

it('provisions and logs in a user from the OIDC callback', function (): void {
    Http::fake([
        'https://keycloak.test/realms/cachet/.well-known/openid-configuration' => Http::response([
            'authorization_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/auth',
            'token_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/token',
            'userinfo_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/userinfo',
        ]),
        'https://keycloak.test/realms/cachet/protocol/openid-connect/token' => Http::response([
            'access_token' => 'access-token',
            'id_token' => 'header.'.rtrim(strtr(base64_encode(json_encode([
                'sub' => 'oidc-user-1',
                'email' => 'oidc@example.com',
                'name' => 'OIDC User',
            ])), '+/', '-_'), '=').'.sig',
            'token_type' => 'Bearer',
        ]),
        'https://keycloak.test/realms/cachet/protocol/openid-connect/userinfo' => Http::response([
            'sub' => 'oidc-user-1',
            'email' => 'oidc@example.com',
            'name' => 'OIDC User',
        ]),
    ]);

    $state = 'test-state-value';

    $response = $this->withSession(['oidc.state' => $state, 'oidc.nonce' => 'nonce'])
        ->get(route('auth.oidc.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'oidc@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth_provider)->toBe('oidc')
        ->and($user->external_id)->toBe('oidc-user-1')
        ->and($user->password)->toBeNull()
        ->and($user->is_admin)->toBeTrue();
});

it('returns not found when OIDC is disabled', function (): void {
    config(['auth_integrations.oidc.enabled' => false]);

    $this->get(route('auth.oidc.redirect'))->assertNotFound();
});

it('still serves local login when OIDC is enabled', function (): void {
    $this->get('/dashboard/login')
        ->assertOk()
        ->assertSee('Sign in with SSO', false);
});
