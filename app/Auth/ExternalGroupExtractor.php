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

class ExternalGroupExtractor
{
    /**
     * Extract group/role names from an OIDC claims payload.
     *
     * @param  array<string, mixed>  $claims
     * @return list<string>
     */
    public function fromOidcClaims(array $claims): array
    {
        $paths = config('auth_integrations.oidc.groups_claim_paths', ['groups', 'realm_access.roles', 'resource_access.cachet.roles']);
        $groups = [];

        foreach ($paths as $path) {
            $value = data_get($claims, $path);

            if (is_string($value) && $value !== '') {
                $groups[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if (is_string($item) && $item !== '') {
                        $groups[] = $item;
                    }
                }
            }
        }

        return array_values(array_unique($groups));
    }
}
