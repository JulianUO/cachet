<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Auth\ExternalUserProvisioner;
use App\Auth\Permission;
use App\Auth\PermissionMatrix;
use App\Auth\Role;
use App\Filament\Pages\Settings\ManageAuthorization;
use App\Models\User;
use Cachet\Models\Incident;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

it('grants public viewers only status view by default', function (): void {
    $matrix = app(PermissionMatrix::class);

    expect($matrix->roleAllows(Role::PublicViewer->value, Permission::StatusView))->toBeTrue()
        ->and($matrix->roleAllows(Role::PublicViewer->value, Permission::DashboardAccess))->toBeFalse()
        ->and($matrix->roleAllows(Role::PublicViewer->value, Permission::IncidentsManage))->toBeFalse();
});

it('allows registered viewers to access the dashboard read-only', function (): void {
    $user = User::forceCreate([
        'name' => 'Viewer',
        'email' => 'viewer@example.com',
        'password' => 'password',
        'role' => Role::RegisteredViewer->value,
        'is_admin' => false,
    ]);

    expect($user->hasPermission(Permission::DashboardAccess))->toBeTrue()
        ->and($user->hasPermission(Permission::IncidentsView))->toBeTrue()
        ->and($user->hasPermission(Permission::IncidentsManage))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('cachet')))->toBeTrue();

    expect(Gate::forUser($user)->allows('create', Incident::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Incident::class))->toBeTrue();
});

it('allows it support to manage incidents but not settings', function (): void {
    $user = User::forceCreate([
        'name' => 'Support',
        'email' => 'support@example.com',
        'password' => 'password',
        'role' => Role::ItSupport->value,
        'is_admin' => false,
    ]);

    expect($user->hasPermission(Permission::IncidentsManage))->toBeTrue()
        ->and($user->hasPermission(Permission::IncidentsDelete))->toBeTrue()
        ->and($user->hasPermission(Permission::ComponentsManage))->toBeTrue()
        ->and($user->hasPermission(Permission::SettingsManage))->toBeFalse()
        ->and($user->hasPermission(Permission::AuthorizationManage))->toBeFalse()
        ->and($user->hasPermission(Permission::UsersManage))->toBeFalse();

    expect(Gate::forUser($user)->allows('create', Incident::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', new Incident))->toBeTrue();
});

it('gives devops the broadest non-admin privileges', function (): void {
    $user = User::forceCreate([
        'name' => 'DevOps',
        'email' => 'devops@example.com',
        'password' => 'password',
        'role' => Role::DevOps->value,
        'is_admin' => false,
    ]);

    expect($user->hasPermission(Permission::SettingsManage))->toBeTrue()
        ->and($user->hasPermission(Permission::AuthorizationManage))->toBeTrue()
        ->and($user->hasPermission(Permission::WebhooksManage))->toBeTrue()
        ->and($user->hasPermission(Permission::ApiWrite))->toBeTrue()
        ->and($user->hasPermission(Permission::UsersManage))->toBeTrue();
});

it('lets is_admin bypass the matrix', function (): void {
    $user = User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin-matrix@example.com',
        'password' => 'password',
        'role' => Role::PublicViewer->value,
        'is_admin' => true,
    ]);

    expect($user->hasPermission(Permission::AuthorizationManage))->toBeTrue()
        ->and($user->hasPermission(Permission::IncidentsDelete))->toBeTrue();
});

it('maps keycloak groups to the highest matching role', function (): void {
    $matrix = app(PermissionMatrix::class);

    expect($matrix->mapExternalGroups(['cachet-registered-viewer', 'cachet-devops'], 'oidc'))
        ->toBe(Role::DevOps->value)
        ->and($matrix->mapExternalGroups(['CN=cachet-it-support,OU=groups,DC=example,DC=com'], 'ldap'))
        ->toBe(Role::ItSupport->value)
        ->and($matrix->mapExternalGroups(['unknown-group'], 'oidc'))
        ->toBeNull();
});

it('assigns mapped roles when provisioning oidc users', function (): void {
    $user = app(ExternalUserProvisioner::class)->provision(
        'oidc',
        [
            'external_id' => 'sub-role-1',
            'email' => 'rolemap@example.com',
            'name' => 'Role Map',
            'groups' => ['cachet-it-support'],
        ],
        autoProvision: true,
        defaultIsAdmin: false,
    );

    expect($user->role)->toBe(Role::ItSupport->value)
        ->and($user->hasPermission(Permission::IncidentsManage))->toBeTrue()
        ->and($user->hasPermission(Permission::SettingsManage))->toBeFalse();
});

it('denies dashboard access for public viewer accounts', function (): void {
    $user = User::forceCreate([
        'name' => 'Public',
        'email' => 'public@example.com',
        'password' => 'password',
        'role' => Role::PublicViewer->value,
        'is_admin' => false,
    ]);

    expect($user->canAccessPanel(filament()->getPanel('cachet')))->toBeFalse();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertForbidden();
});

it('allows devops to open the authorization settings page', function (): void {
    $user = User::forceCreate([
        'name' => 'DevOps Admin',
        'email' => 'devops-authz@example.com',
        'password' => 'password',
        'role' => Role::DevOps->value,
        'is_admin' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ManageAuthorization::class)
        ->assertSuccessful()
        ->assertSee('Role permissions');
});

it('keeps local password login working for role-assigned users', function (): void {
    $user = User::forceCreate([
        'name' => 'Local Support',
        'email' => 'local-support@example.com',
        'password' => 'password',
        'role' => Role::ItSupport->value,
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
