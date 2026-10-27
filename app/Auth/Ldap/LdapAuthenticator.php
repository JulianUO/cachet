<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth\Ldap;

use LDAP\Connection;
use RuntimeException;

class LdapAuthenticator
{
    /**
     * Authenticate against LDAP with bind + search, then user bind.
     *
     * @return array{external_id: string, email: string, name: ?string, username: string, groups: list<string>}|null
     */
    public function attempt(string $username, string $password): ?array
    {
        if (! extension_loaded('ldap')) {
            throw new RuntimeException('The PHP LDAP extension is required for LDAP authentication.');
        }

        if ($username === '' || $password === '') {
            return null;
        }

        $connection = $this->connect();

        try {
            $this->serviceBind($connection);

            $entry = $this->searchUser($connection, $username);

            if ($entry === null) {
                return null;
            }

            $userDn = $entry['dn'] ?? null;

            if (! is_string($userDn) || $userDn === '') {
                return null;
            }

            if (! @ldap_bind($connection, $userDn, $password)) {
                return null;
            }

            // Re-bind as service account for group lookups when configured.
            $this->serviceBind($connection);

            $identity = $this->mapEntry($entry, $username);
            $identity['groups'] = $this->resolveGroups($connection, $entry, $userDn);

            return $identity;
        } finally {
            ldap_unbind($connection);
        }
    }

    /**
     * @return Connection|resource
     */
    private function connect(): mixed
    {
        $host = (string) config('auth_integrations.ldap.host');
        $port = (int) config('auth_integrations.ldap.port', 389);
        $useSsl = (bool) config('auth_integrations.ldap.use_ssl', false);
        $useTls = (bool) config('auth_integrations.ldap.use_tls', false);
        $timeout = (int) config('auth_integrations.ldap.timeout', 5);

        $scheme = $useSsl ? 'ldaps' : 'ldap';
        $uri = sprintf('%s://%s:%d', $scheme, $host, $port);

        $connection = @ldap_connect($uri);

        if ($connection === false) {
            throw new RuntimeException('Unable to connect to the LDAP server.');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, $timeout);

        if ($useTls && ! $useSsl && ! @ldap_start_tls($connection)) {
            throw new RuntimeException('Unable to start TLS on the LDAP connection.');
        }

        return $connection;
    }

    /**
     * @param  Connection|resource  $connection
     */
    private function serviceBind(mixed $connection): void
    {
        $bindDn = config('auth_integrations.ldap.bind_dn');
        $bindPassword = config('auth_integrations.ldap.bind_password');

        if (blank($bindDn)) {
            if (! @ldap_bind($connection)) {
                throw new RuntimeException('Anonymous LDAP bind failed.');
            }

            return;
        }

        if (! @ldap_bind($connection, (string) $bindDn, (string) $bindPassword)) {
            throw new RuntimeException('LDAP service account bind failed.');
        }
    }

    /**
     * @param  Connection|resource  $connection
     * @return array<string, mixed>|null
     */
    private function searchUser(mixed $connection, string $username): ?array
    {
        $baseDn = (string) config('auth_integrations.ldap.base_dn');
        $filterTemplate = (string) config('auth_integrations.ldap.filter', '(uid={username})');
        $filter = str_replace('{username}', $this->escapeFilterValue($username), $filterTemplate);

        $map = config('auth_integrations.ldap.attribute_map', []);
        $groupAttribute = (string) config('auth_integrations.ldap.group_attribute', 'memberOf');
        $attributes = array_values(array_unique(array_filter([
            'dn',
            $map['external_id'] ?? 'entryUUID',
            $map['email'] ?? 'mail',
            $map['name'] ?? 'cn',
            $map['username'] ?? 'uid',
            $groupAttribute,
        ])));

        $search = @ldap_search($connection, $baseDn, $filter, $attributes);

        if ($search === false) {
            return null;
        }

        $entries = ldap_get_entries($connection, $search);

        if (! is_array($entries) || ($entries['count'] ?? 0) < 1) {
            return null;
        }

        return $entries[0];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array{external_id: string, email: string, name: ?string, username: string}
     */
    private function mapEntry(array $entry, string $username): array
    {
        $map = config('auth_integrations.ldap.attribute_map', []);

        $externalId = $this->attribute($entry, (string) ($map['external_id'] ?? 'entryUUID'))
            ?: $this->attribute($entry, (string) ($map['username'] ?? 'uid'))
            ?: $username;

        $email = $this->attribute($entry, (string) ($map['email'] ?? 'mail'));

        if ($email === '') {
            $email = str_contains($username, '@') ? $username : $username.'@ldap.local';
        }

        return [
            'external_id' => $externalId,
            'email' => $email,
            'name' => $this->attribute($entry, (string) ($map['name'] ?? 'cn')) ?: $username,
            'username' => $username,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function attribute(array $entry, string $name): string
    {
        $key = strtolower($name);

        foreach ($entry as $attribute => $value) {
            if (! is_string($attribute) || strtolower($attribute) !== $key) {
                continue;
            }

            if (is_array($value)) {
                return (string) ($value[0] ?? '');
            }

            return (string) $value;
        }

        return '';
    }

    /**
     * @param  Connection|resource  $connection
     * @param  array<string, mixed>  $entry
     * @return list<string>
     */
    private function resolveGroups(mixed $connection, array $entry, string $userDn): array
    {
        $groups = [];
        $groupAttribute = (string) config('auth_integrations.ldap.group_attribute', 'memberOf');

        $memberOf = $entry[strtolower($groupAttribute)] ?? $entry[$groupAttribute] ?? null;

        if (is_array($memberOf)) {
            foreach ($memberOf as $key => $value) {
                if ($key === 'count' || ! is_string($value) || $value === '') {
                    continue;
                }

                $groups[] = $value;
            }
        }

        $groupFilter = (string) config('auth_integrations.ldap.group_filter', '');

        if ($groupFilter !== '') {
            $filter = str_replace('{dn}', $this->escapeFilterValue($userDn), $groupFilter);
            $baseDn = (string) config('auth_integrations.ldap.base_dn');
            $search = @ldap_search($connection, $baseDn, $filter, ['cn', 'dn']);

            if ($search !== false) {
                $entries = ldap_get_entries($connection, $search);

                if (is_array($entries)) {
                    for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                        $groupEntry = $entries[$i];
                        $cn = $this->attribute($groupEntry, 'cn');
                        $dn = is_string($groupEntry['dn'] ?? null) ? $groupEntry['dn'] : '';

                        if ($cn !== '') {
                            $groups[] = $cn;
                        }

                        if ($dn !== '') {
                            $groups[] = $dn;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($groups));
    }

    private function escapeFilterValue(string $value): string
    {
        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value,
        );
    }
}
