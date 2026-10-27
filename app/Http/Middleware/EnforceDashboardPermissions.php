<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Middleware;

use App\Auth\Permission;
use App\Filament\Pages\Integrations\ManageAuthentication;
use App\Filament\Pages\Settings\ManageAuthorization;
use App\Models\User;
use Cachet\Filament\Pages\Integrations\OhDear;
use Cachet\Filament\Pages\Settings\ManageCachet;
use Cachet\Filament\Pages\Settings\ManageCustomization;
use Cachet\Filament\Pages\Settings\ManageLocalization;
use Cachet\Filament\Pages\Settings\ManageNotifications;
use Cachet\Filament\Pages\Settings\ManageTheme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDashboardPermissions
{
    /**
     * Map Filament page classes to required permissions.
     *
     * @var array<class-string, Permission>
     */
    private array $pagePermissions = [
        ManageCachet::class => Permission::SettingsManage,
        ManageCustomization::class => Permission::SettingsManage,
        ManageLocalization::class => Permission::SettingsManage,
        ManageNotifications::class => Permission::SettingsManage,
        ManageTheme::class => Permission::SettingsManage,
        OhDear::class => Permission::SettingsManage,
        ManageAuthentication::class => Permission::AuthIntegrationsManage,
        ManageAuthorization::class => Permission::AuthorizationManage,
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->hasPermission(Permission::DashboardAccess)) {
            abort(403, 'Your role cannot access the dashboard.');
        }

        $component = $request->route()?->getAction('service')
            ?? $request->route()?->getControllerClass()
            ?? $request->route()?->getActionName();

        if (is_string($component) && isset($this->pagePermissions[$component])) {
            if (! $user->hasPermission($this->pagePermissions[$component])) {
                abort(403, 'Your role cannot access this settings page.');
            }
        }

        return $next($request);
    }
}
