<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AuthorizationSettings extends Settings
{
    /**
     * Role → list of permission keys. Empty means "use config defaults".
     */
    public mixed $matrix = [];

    /**
     * External OIDC group/role name → Cachet role.
     */
    public mixed $oidc_role_map = [];

    /**
     * LDAP group CN/DN → Cachet role.
     */
    public mixed $ldap_role_map = [];

    public static function group(): string
    {
        return 'authorization';
    }
}
