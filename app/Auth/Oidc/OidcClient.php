<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth\Oidc;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OidcClient
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $discovery = null;

    /**
     * @return array{authorization_url: string, state: string, nonce: string}
     */
    public function authorizationRequest(): array
    {
        $discovery = $this->discover();
        $state = Str::random(40);
        $nonce = Str::random(40);

        $query = http_build_query([
            'client_id' => config('auth_integrations.oidc.client_id'),
            'redirect_uri' => config('auth_integrations.oidc.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', config('auth_integrations.oidc.scopes', ['openid', 'profile', 'email'])),
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return [
            'authorization_url' => rtrim((string) $discovery['authorization_endpoint'], '?').'?'.$query,
            'state' => $state,
            'nonce' => $nonce,
        ];
    }

    /**
     * Exchange an authorization code and resolve the user identity.
     *
     * @return array{external_id: string, email: string, name: ?string, claims: array<string, mixed>}
     */
    public function authenticateWithCode(string $code): array
    {
        $discovery = $this->discover();

        try {
            $tokenResponse = Http::asForm()
                ->acceptJson()
                ->post((string) $discovery['token_endpoint'], [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('auth_integrations.oidc.redirect'),
                    'client_id' => config('auth_integrations.oidc.client_id'),
                    'client_secret' => config('auth_integrations.oidc.client_secret'),
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException('OIDC token exchange failed: '.$exception->getMessage(), previous: $exception);
        }

        $claims = [];

        if (filled($tokenResponse['id_token'] ?? null)) {
            $claims = $this->decodeJwtPayload((string) $tokenResponse['id_token']);
        }

        if (filled($tokenResponse['access_token'] ?? null) && filled($discovery['userinfo_endpoint'] ?? null)) {
            try {
                $userinfo = Http::withToken((string) $tokenResponse['access_token'])
                    ->acceptJson()
                    ->get((string) $discovery['userinfo_endpoint'])
                    ->throw()
                    ->json();

                if (is_array($userinfo)) {
                    $claims = array_merge($claims, $userinfo);
                }
            } catch (RequestException $exception) {
                throw new RuntimeException('OIDC userinfo request failed: '.$exception->getMessage(), previous: $exception);
            }
        }

        $map = config('auth_integrations.oidc.attribute_map', []);

        return [
            'external_id' => (string) data_get($claims, $map['external_id'] ?? 'sub', ''),
            'email' => (string) data_get($claims, $map['email'] ?? 'email', ''),
            'name' => data_get($claims, $map['name'] ?? 'name'),
            'claims' => $claims,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function discover(): array
    {
        if ($this->discovery !== null) {
            return $this->discovery;
        }

        $issuer = rtrim((string) config('auth_integrations.oidc.issuer'), '/');

        if ($issuer === '') {
            throw new RuntimeException('OIDC issuer is not configured.');
        }

        try {
            $this->discovery = Http::acceptJson()
                ->get($issuer.'/.well-known/openid-configuration')
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException('OIDC discovery failed: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($this->discovery) || blank($this->discovery['authorization_endpoint'] ?? null) || blank($this->discovery['token_endpoint'] ?? null)) {
            throw new RuntimeException('OIDC discovery document is missing required endpoints.');
        }

        return $this->discovery;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            return [];
        }

        $payload = $parts[1];
        $remainder = strlen($payload) % 4;

        if ($remainder > 0) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/'), true) ?: '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
