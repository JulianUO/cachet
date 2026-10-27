<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksResourcePermissions;

class ComponentGroupPolicy
{
    use ChecksResourcePermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ComponentsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::ComponentGroupsManage;
    }

    protected function deletePermission(): Permission
    {
        return Permission::ComponentGroupsManage;
    }

    public function viewAny(?User $user): bool
    {
        return $this->allows($user, Permission::ComponentsView)
            || $this->allows($user, Permission::ComponentGroupsManage);
    }
}
