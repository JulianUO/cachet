<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Policies\Concerns;

use App\Auth\Permission;
use App\Models\User;

trait ChecksResourcePermissions
{
    abstract protected function viewPermission(): Permission;

    abstract protected function managePermission(): Permission;

    abstract protected function deletePermission(): Permission;

    public function viewAny(?User $user): bool
    {
        return $this->allows($user, $this->viewPermission())
            || $this->allows($user, $this->managePermission());
    }

    public function view(?User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(?User $user): bool
    {
        return $this->allows($user, $this->managePermission());
    }

    public function update(?User $user, mixed $model): bool
    {
        return $this->allows($user, $this->managePermission());
    }

    public function delete(?User $user, mixed $model): bool
    {
        return $this->allows($user, $this->deletePermission());
    }

    public function deleteAny(?User $user): bool
    {
        return $this->allows($user, $this->deletePermission());
    }

    public function restore(?User $user, mixed $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(?User $user, mixed $model): bool
    {
        return $this->delete($user, $model);
    }

    public function reorder(?User $user): bool
    {
        return $this->allows($user, $this->managePermission());
    }

    protected function allows(?User $user, Permission $permission): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission($permission);
    }
}
