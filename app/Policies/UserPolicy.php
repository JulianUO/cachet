<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->hasPermission(Permission::UsersManage) ?? false;
    }

    public function view(?User $user, mixed $model): bool
    {
        return $this->viewAny($user) || ($user && $model instanceof User && $user->is($model));
    }

    public function create(?User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(?User $user, mixed $model): bool
    {
        return $this->viewAny($user) || ($user && $model instanceof User && $user->is($model));
    }

    public function delete(?User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(?User $user): bool
    {
        return $this->viewAny($user);
    }
}
