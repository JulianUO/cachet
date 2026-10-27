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

use App\Auth\AppliesAuthIntegrationSettings;
use App\Filament\Auth\Login;
use App\Filament\Pages\Integrations\ManageAuthentication;
use App\Filament\Pages\Integrations\ManageMail;
use App\Mail\AppliesMailTransportSettings;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AuthIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/auth_integrations.php'),
            'auth_integrations',
        );

        // Run after CachetDashboardServiceProvider has registered the panel.
        $this->app->resolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            $panel = $registry->get('cachet');

            if ($panel === null) {
                return;
            }

            $panel
                ->login(Login::class)
                ->pages([
                    ManageAuthentication::class,
                    ManageMail::class,
                ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Panel::register() already ran with the stock Login Livewire alias;
        // register our replacements with the same names Livewire/Filament expect.
        Livewire::component(Login::class, Login::class);
        Livewire::component(ManageAuthentication::class, ManageAuthentication::class);
        Livewire::component(ManageMail::class, ManageMail::class);

        Route::middleware('web')
            ->group(base_path('routes/auth.php'));

        // Overlay dashboard auth settings after the app is fully booted so
        // migrations (and the settings table) are available in HTTP / artisan.
        $this->app->booted(function (): void {
            AppliesAuthIntegrationSettings::apply();
        });

        // Enrich core MailSettings override with SMTP encryption from the dashboard.
        $this->callAfterResolving('mail.manager', function (): void {
            AppliesMailTransportSettings::apply();
        });
    }
}
