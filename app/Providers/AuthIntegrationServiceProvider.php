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

use App\Filament\Auth\Login;
use App\Filament\Pages\Integrations\ManageAuthentication;
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

        Route::middleware('web')
            ->group(base_path('routes/auth.php'));
    }
}
