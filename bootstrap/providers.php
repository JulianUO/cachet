<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthIntegrationServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\SlackNotificationServiceProvider;
use Cachet\CachetCoreServiceProvider;
use Cachet\CachetDashboardServiceProvider;

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    AppServiceProvider::class,
    CachetCoreServiceProvider::class,
    CachetDashboardServiceProvider::class,
    // After the Cachet Filament panel so we can replace login and add pages.
    AuthIntegrationServiceProvider::class,
    AuthorizationServiceProvider::class,
    SlackNotificationServiceProvider::class,
];
