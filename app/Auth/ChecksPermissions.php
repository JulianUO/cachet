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

trait ChecksPermissions
{
    public function hasPermission(Permission|string $permission): bool
    {
        return app(PermissionMatrix::class)->userAllows($this instanceof User ? $this : null, $permission);
    }

    public function effectiveRole(): string
    {
        if ($this instanceof User && filled($this->role)) {
            return (string) $this->role;
        }

        return (string) config('authorization.default_role', Role::RegisteredViewer->value);
    }
}
