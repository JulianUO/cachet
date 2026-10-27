<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth;

enum Permission: string
{
    case StatusView = 'status.view';
    case DashboardAccess = 'dashboard.access';
    case IncidentsView = 'incidents.view';
    case IncidentsManage = 'incidents.manage';
    case IncidentsDelete = 'incidents.delete';
    case ComponentsView = 'components.view';
    case ComponentsManage = 'components.manage';
    case ComponentsDelete = 'components.delete';
    case ComponentGroupsManage = 'component_groups.manage';
    case SchedulesView = 'schedules.view';
    case SchedulesManage = 'schedules.manage';
    case SchedulesDelete = 'schedules.delete';
    case MetricsView = 'metrics.view';
    case MetricsManage = 'metrics.manage';
    case MetricsDelete = 'metrics.delete';
    case SubscribersManage = 'subscribers.manage';
    case IncidentTemplatesManage = 'incident_templates.manage';
    case WebhooksManage = 'webhooks.manage';
    case ApiKeysManage = 'api_keys.manage';
    case ApiWrite = 'api.write';
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';
    case AuthIntegrationsManage = 'auth_integrations.manage';
    case AuthorizationManage = 'authorization.manage';

    public function label(): string
    {
        $label = config('authorization.permissions')[$this->value] ?? $this->value;

        return __((string) $label);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (config('authorization.permissions', []) as $permission => $label) {
            $options[$permission] = __((string) $label);
        }

        return $options;
    }
}
