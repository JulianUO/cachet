<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        rescue(fn () => $this->migrator->add('authorization.matrix', []));
        rescue(fn () => $this->migrator->add('authorization.oidc_role_map', []));
        rescue(fn () => $this->migrator->add('authorization.ldap_role_map', []));
    }
};
