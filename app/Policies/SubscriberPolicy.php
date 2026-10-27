<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Models\User;

class SubscriberPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->hasPermission(Permission::SubscribersManage) ?? false;
    }

    public function view(?User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(?User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(?User $user, mixed $model): bool
    {
        return $this->viewAny($user);
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
