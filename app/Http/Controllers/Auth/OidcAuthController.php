<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Controllers\Auth;

use App\Auth\AuthIntegrations;
use App\Auth\ExternalUserProvisioner;
use App\Auth\Oidc\OidcClient;
use App\Http\Controllers\Controller;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OidcAuthController extends Controller
{
    public function redirect(OidcClient $client): RedirectResponse
    {
        abort_unless(AuthIntegrations::oidcEnabled(), 404);

        $request = $client->authorizationRequest();

        session([
            'oidc.state' => $request['state'],
            'oidc.nonce' => $request['nonce'],
        ]);

        return redirect()->away($request['authorization_url']);
    }

    public function callback(Request $request, OidcClient $client, ExternalUserProvisioner $provisioner): RedirectResponse
    {
        abort_unless(AuthIntegrations::oidcEnabled(), 404);

        $loginUrl = Filament::getPanel('cachet')->getLoginUrl() ?? url('/dashboard/login');

        if ($request->filled('error')) {
            return redirect($loginUrl)->withErrors([
                'oidc' => 'OIDC provider returned an error: '.$request->string('error'),
            ]);
        }

        $state = (string) $request->query('state', '');
        $expectedState = (string) session()->pull('oidc.state', '');
        session()->forget('oidc.nonce');

        if ($state === '' || $expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect($loginUrl)->withErrors([
                'oidc' => 'Invalid OIDC state. Please try signing in again.',
            ]);
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return redirect($loginUrl)->withErrors([
                'oidc' => 'OIDC authorization code was missing.',
            ]);
        }

        try {
            $identity = $client->authenticateWithCode($code);
            $user = $provisioner->provision(
                'oidc',
                $identity,
                (bool) config('auth_integrations.oidc.auto_provision', true),
                (bool) config('auth_integrations.oidc.default_is_admin', false),
            );
        } catch (RuntimeException $exception) {
            Log::warning('OIDC authentication failed', ['message' => $exception->getMessage()]);

            return redirect($loginUrl)->withErrors([
                'oidc' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('OIDC authentication error', ['exception' => $exception]);

            return redirect($loginUrl)->withErrors([
                'oidc' => 'Unable to complete OIDC sign-in.',
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getPanel('cachet')->getUrl() ?? url('/dashboard'));
    }
}
