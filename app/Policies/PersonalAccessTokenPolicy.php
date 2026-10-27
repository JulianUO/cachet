<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->hasPermission(Permission::ApiKeysManage) ?? false;
    }

    public function view(?User $user, PersonalAccessToken $token): bool
    {
        return $this->owns($user, $token) && $this->viewAny($user);
    }

    public function create(?User $user): bool
    {
        return $user?->hasPermission(Permission::ApiKeysManage) ?? false;
    }

    public function update(?User $user, PersonalAccessToken $token): bool
    {
        return $this->owns($user, $token) && $this->viewAny($user);
    }

    public function delete(?User $user, PersonalAccessToken $token): bool
    {
        return $this->owns($user, $token) && $this->viewAny($user);
    }

    public function deleteAny(?User $user): bool
    {
        return $this->viewAny($user);
    }

    private function owns(?User $user, PersonalAccessToken $token): bool
    {
        return $user !== null
            && $token->tokenable_type === $user::class
            && (int) $token->tokenable_id === (int) $user->getKey();
    }
}
