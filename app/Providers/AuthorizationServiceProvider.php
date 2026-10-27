<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Providers;

use App\Auth\Permission;
use App\Filament\Pages\Settings\ManageAuthorization;
use App\Http\Middleware\EnforceDashboardPermissions;
use App\Policies\ComponentGroupPolicy;
use App\Policies\ComponentPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\IncidentTemplatePolicy;
use App\Policies\MetricPolicy;
use App\Policies\PersonalAccessTokenPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SubscriberPolicy;
use App\Policies\UserPolicy;
use App\Policies\WebhookSubscriptionPolicy;
use Cachet\Cachet;
use Cachet\Models\Component;
use Cachet\Models\ComponentGroup;
use Cachet\Models\Incident;
use Cachet\Models\IncidentTemplate;
use Cachet\Models\Metric;
use Cachet\Models\Schedule;
use Cachet\Models\Subscriber;
use Cachet\Models\User as CachetUser;
use Cachet\Models\WebhookSubscription;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/authorization.php'),
            'authorization',
        );

        $this->app->resolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            $panel = $registry->get('cachet');

            if ($panel === null) {
                return;
            }

            $panel
                ->pages([
                    ManageAuthorization::class,
                ])
                ->authMiddleware([
                    EnforceDashboardPermissions::class,
                ], isPersistent: true);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Livewire::component(ManageAuthorization::class, ManageAuthorization::class);

        Gate::policy(Incident::class, IncidentPolicy::class);
        Gate::policy(Component::class, ComponentPolicy::class);
        Gate::policy(ComponentGroup::class, ComponentGroupPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(Metric::class, MetricPolicy::class);
        Gate::policy(Subscriber::class, SubscriberPolicy::class);
        Gate::policy(IncidentTemplate::class, IncidentTemplatePolicy::class);
        Gate::policy(WebhookSubscription::class, WebhookSubscriptionPolicy::class);
        Gate::policy(PersonalAccessToken::class, PersonalAccessTokenPolicy::class);
        Gate::policy(CachetUser::class, UserPolicy::class);
        Gate::policy(\App\Models\User::class, UserPolicy::class);

        Cachet::authorizeDashboardFeatureUsing('themes', function (): bool {
            $user = auth()->user();

            return $user?->isAdmin() || $user?->hasPermission(Permission::SettingsManage) || false;
        });

        Cachet::authorizeDashboardFeatureUsing('webhooks', function (): bool {
            $user = auth()->user();

            return $user?->isAdmin() || $user?->hasPermission(Permission::WebhooksManage) || false;
        });
    }
}
