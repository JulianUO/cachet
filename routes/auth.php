<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Http\Controllers\Auth\OidcAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/oidc/redirect', [OidcAuthController::class, 'redirect'])
        ->name('auth.oidc.redirect');

    Route::get('/auth/oidc/callback', [OidcAuthController::class, 'callback'])
        ->name('auth.oidc.callback');
});
