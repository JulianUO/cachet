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

use App\Models\User;
use App\Settings\AuthorizationSettings;
use Throwable;

class PermissionMatrix
{
    /**
     * @return array<string, list<string>>
     */
    public function matrix(): array
    {
        $defaults = config('authorization.matrix', []);

        try {
            $settings = app(AuthorizationSettings::class);
            $override = $settings->matrix;

            if (is_array($override) && $override !== []) {
                return array_replace_recursive($defaults, $this->normalizeMatrix($override));
            }
        } catch (Throwable) {
            // Settings table may not be migrated yet in early boot/tests.
        }

        return $defaults;
    }

    /**
     * @return list<string>
     */
    public function permissionsFor(?string $role): array
    {
        if ($role === null || $role === '') {
            $role = (string) config('authorization.default_role', Role::RegisteredViewer->value);
        }

        $permissions = $this->matrix()[$role] ?? [];

        return array_values(array_unique(array_map('strval', $permissions)));
    }

    public function roleAllows(?string $role, Permission|string $permission): bool
    {
        $permission = $permission instanceof Permission ? $permission->value : $permission;

        return in_array($permission, $this->permissionsFor($role), true);
    }

    public function userAllows(?User $user, Permission|string $permission): bool
    {
        if ($user === null) {
            return $this->roleAllows(Role::PublicViewer->value, $permission);
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->roleAllows($user->role, $permission);
    }

    /**
     * @return array<string, string>
     */
    public function oidcRoleMap(): array
    {
        return $this->roleMap('oidc_role_map');
    }

    /**
     * @return array<string, string>
     */
    public function ldapRoleMap(): array
    {
        return $this->roleMap('ldap_role_map');
    }

    /**
     * Pick the highest-precedence Cachet role matched by external group names.
     *
     * @param  list<string>  $externalGroups
     */
    public function mapExternalGroups(array $externalGroups, string $provider): ?string
    {
        $map = $provider === 'ldap' ? $this->ldapRoleMap() : $this->oidcRoleMap();
        $normalizedMap = [];

        foreach ($map as $external => $role) {
            $normalizedMap[mb_strtolower(trim((string) $external))] = (string) $role;
        }

        $matched = [];

        foreach ($externalGroups as $group) {
            $candidates = $this->groupMatchKeys((string) $group);

            foreach ($candidates as $candidate) {
                if (isset($normalizedMap[$candidate])) {
                    $matched[] = $normalizedMap[$candidate];
                }
            }
        }

        if ($matched === []) {
            return null;
        }

        foreach (config('authorization.role_precedence', []) as $role) {
            if (in_array($role, $matched, true)) {
                return $role;
            }
        }

        return $matched[0];
    }

    /**
     * @param  array<string, mixed>  $matrix
     * @return array<string, list<string>>
     */
    private function normalizeMatrix(array $matrix): array
    {
        $normalized = [];

        foreach ($matrix as $role => $permissions) {
            if (! is_array($permissions)) {
                continue;
            }

            // Support both list of permission strings and map of permission => bool.
            if (array_is_list($permissions)) {
                $normalized[$role] = array_values(array_map('strval', $permissions));

                continue;
            }

            $normalized[$role] = [];

            foreach ($permissions as $permission => $allowed) {
                if ($allowed) {
                    $normalized[$role][] = (string) $permission;
                }
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function roleMap(string $key): array
    {
        $defaults = config("authorization.{$key}", []);

        try {
            $settings = app(AuthorizationSettings::class);
            $override = $key === 'oidc_role_map' ? $settings->oidc_role_map : $settings->ldap_role_map;

            if (is_array($override) && $override !== []) {
                return array_merge($defaults, $override);
            }
        } catch (Throwable) {
            //
        }

        return is_array($defaults) ? $defaults : [];
    }

    /**
     * @return list<string>
     */
    private function groupMatchKeys(string $group): array
    {
        $group = trim($group);
        $keys = [mb_strtolower($group)];

        if (str_starts_with($group, '/')) {
            $keys[] = mb_strtolower(ltrim($group, '/'));
        }

        if (preg_match('/(?:^|,)cn=([^,]+)/i', $group, $matches) === 1) {
            $keys[] = mb_strtolower(trim($matches[1]));
        }

        return array_values(array_unique($keys));
    }
}
