<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Auth\ExternalGroupExtractor;
use App\Auth\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'auth_integrations.oidc.enabled' => true,
        'auth_integrations.oidc.issuer' => 'https://keycloak.test/realms/cachet',
        'auth_integrations.oidc.client_id' => 'cachet',
        'auth_integrations.oidc.client_secret' => 'secret',
        'auth_integrations.oidc.redirect' => 'http://cachet.test/auth/oidc/callback',
        'auth_integrations.oidc.auto_provision' => true,
        'auth_integrations.oidc.default_is_admin' => false,
        'auth_integrations.oidc.groups_claim_paths' => ['groups', 'realm_access.roles'],
    ]);
});

it('extracts keycloak realm roles and groups from claims', function (): void {
    $groups = app(ExternalGroupExtractor::class)->fromOidcClaims([
        'groups' => ['/cachet-registered-viewer'],
        'realm_access' => [
            'roles' => ['cachet-devops', 'offline_access'],
        ],
    ]);

    expect($groups)->toContain('cachet-devops')
        ->toContain('/cachet-registered-viewer');
});

it('provisions devops role from keycloak realm roles on callback', function (): void {
    Http::fake([
        'https://keycloak.test/realms/cachet/.well-known/openid-configuration' => Http::response([
            'authorization_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/auth',
            'token_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/token',
            'userinfo_endpoint' => 'https://keycloak.test/realms/cachet/protocol/openid-connect/userinfo',
        ]),
        'https://keycloak.test/realms/cachet/protocol/openid-connect/token' => Http::response([
            'access_token' => 'access-token',
            'id_token' => 'header.'.rtrim(strtr(base64_encode(json_encode([
                'sub' => 'oidc-devops-1',
                'email' => 'devops-oidc@example.com',
                'name' => 'OIDC DevOps',
                'realm_access' => ['roles' => ['cachet-devops']],
            ])), '+/', '-_'), '=').'.sig',
            'token_type' => 'Bearer',
        ]),
        'https://keycloak.test/realms/cachet/protocol/openid-connect/userinfo' => Http::response([
            'sub' => 'oidc-devops-1',
            'email' => 'devops-oidc@example.com',
            'name' => 'OIDC DevOps',
            'realm_access' => ['roles' => ['cachet-devops']],
        ]),
    ]);

    $state = 'role-state';

    $this->withSession(['oidc.state' => $state])
        ->get(route('auth.oidc.callback', ['code' => 'code', 'state' => $state]))
        ->assertRedirect('/dashboard');

    $user = User::query()->where('email', 'devops-oidc@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::DevOps->value);
});
