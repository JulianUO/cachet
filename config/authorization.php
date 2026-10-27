<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Auth\Permission;
use App\Auth\Role;

return [

    /*
    |--------------------------------------------------------------------------
    | Default role for newly provisioned users
    |--------------------------------------------------------------------------
    |
    | Used when OIDC/LDAP group mapping does not match and for local users
    | created without an explicit role.
    |
    */
    'default_role' => env('AUTH_DEFAULT_ROLE', Role::RegisteredViewer->value),

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        Role::PublicViewer->value => 'Public viewer',
        Role::RegisteredViewer->value => 'Registered viewer',
        Role::ItSupport->value => 'IT support',
        Role::DevOps->value => 'DevOps',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        Permission::StatusView->value => 'View public status page',
        Permission::DashboardAccess->value => 'Access dashboard',
        Permission::IncidentsView->value => 'View incidents',
        Permission::IncidentsManage->value => 'Manage incidents',
        Permission::IncidentsDelete->value => 'Delete incidents',
        Permission::ComponentsView->value => 'View components',
        Permission::ComponentsManage->value => 'Manage components',
        Permission::ComponentsDelete->value => 'Delete components',
        Permission::ComponentGroupsManage->value => 'Manage component groups',
        Permission::SchedulesView->value => 'View schedules',
        Permission::SchedulesManage->value => 'Manage schedules',
        Permission::SchedulesDelete->value => 'Delete schedules',
        Permission::MetricsView->value => 'View metrics',
        Permission::MetricsManage->value => 'Manage metrics',
        Permission::MetricsDelete->value => 'Delete metrics',
        Permission::SubscribersManage->value => 'Manage subscribers',
        Permission::IncidentTemplatesManage->value => 'Manage incident templates',
        Permission::WebhooksManage->value => 'Manage webhooks',
        Permission::ApiKeysManage->value => 'Manage API keys',
        Permission::ApiWrite->value => 'Create write-capable API tokens',
        Permission::UsersManage->value => 'Manage users',
        Permission::SettingsManage->value => 'Manage settings',
        Permission::AuthIntegrationsManage->value => 'Manage auth integrations',
        Permission::AuthorizationManage->value => 'Edit permission matrix',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default permission matrix
    |--------------------------------------------------------------------------
    |
    | Overridable from the Filament "Authorization" settings page (stored in
    | Spatie settings). is_admin users always bypass this matrix.
    |
    */
    'matrix' => [
        Role::PublicViewer->value => [
            Permission::StatusView->value,
        ],
        Role::RegisteredViewer->value => [
            Permission::StatusView->value,
            Permission::DashboardAccess->value,
            Permission::IncidentsView->value,
            Permission::ComponentsView->value,
            Permission::SchedulesView->value,
            Permission::MetricsView->value,
        ],
        Role::ItSupport->value => [
            Permission::StatusView->value,
            Permission::DashboardAccess->value,
            Permission::IncidentsView->value,
            Permission::IncidentsManage->value,
            Permission::IncidentsDelete->value,
            Permission::ComponentsView->value,
            Permission::ComponentsManage->value,
            Permission::SchedulesView->value,
            Permission::SchedulesManage->value,
            Permission::MetricsView->value,
            Permission::SubscribersManage->value,
            Permission::ApiKeysManage->value,
        ],
        Role::DevOps->value => [
            Permission::StatusView->value,
            Permission::DashboardAccess->value,
            Permission::IncidentsView->value,
            Permission::IncidentsManage->value,
            Permission::IncidentsDelete->value,
            Permission::ComponentsView->value,
            Permission::ComponentsManage->value,
            Permission::ComponentsDelete->value,
            Permission::ComponentGroupsManage->value,
            Permission::SchedulesView->value,
            Permission::SchedulesManage->value,
            Permission::SchedulesDelete->value,
            Permission::MetricsView->value,
            Permission::MetricsManage->value,
            Permission::MetricsDelete->value,
            Permission::SubscribersManage->value,
            Permission::IncidentTemplatesManage->value,
            Permission::WebhooksManage->value,
            Permission::ApiKeysManage->value,
            Permission::ApiWrite->value,
            Permission::UsersManage->value,
            Permission::SettingsManage->value,
            Permission::AuthIntegrationsManage->value,
            Permission::AuthorizationManage->value,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OIDC / Keycloak group or role → Cachet role
    |--------------------------------------------------------------------------
    |
    | Keys are IdP group/role names (from OIDC_GROUPS_CLAIM). Values are Cachet
    | role keys. Example Keycloak realm roles: cachet-devops, cachet-it-support.
    |
    | Env JSON override: OIDC_ROLE_MAP={"cachet-devops":"devops","cachet-it-support":"it_support"}
    |
    */
    'oidc_role_map' => json_decode(env('OIDC_ROLE_MAP', '[]'), true) ?: [
        'cachet-devops' => Role::DevOps->value,
        'devops' => Role::DevOps->value,
        'cachet-it-support' => Role::ItSupport->value,
        'it-support' => Role::ItSupport->value,
        'it_support' => Role::ItSupport->value,
        'cachet-registered-viewer' => Role::RegisteredViewer->value,
        'registered-viewer' => Role::RegisteredViewer->value,
        'registered_viewer' => Role::RegisteredViewer->value,
        'cachet-public-viewer' => Role::PublicViewer->value,
        'public-viewer' => Role::PublicViewer->value,
        'public_viewer' => Role::PublicViewer->value,
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP group → Cachet role
    |--------------------------------------------------------------------------
    |
    | Keys are LDAP group CNs or full DNs (matched case-insensitively against
    | memberOf values and CN= extractions). Env JSON: LDAP_ROLE_MAP={...}
    |
    */
    'ldap_role_map' => json_decode(env('LDAP_ROLE_MAP', '[]'), true) ?: [
        'cachet-devops' => Role::DevOps->value,
        'devops' => Role::DevOps->value,
        'cachet-it-support' => Role::ItSupport->value,
        'it-support' => Role::ItSupport->value,
        'cachet-registered-viewer' => Role::RegisteredViewer->value,
        'registered-viewer' => Role::RegisteredViewer->value,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role precedence when multiple IdP groups match
    |--------------------------------------------------------------------------
    */
    'role_precedence' => [
        Role::DevOps->value,
        Role::ItSupport->value,
        Role::RegisteredViewer->value,
        Role::PublicViewer->value,
    ],

];
