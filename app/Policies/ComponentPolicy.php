<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Policies\Concerns\ChecksResourcePermissions;

class ComponentPolicy
{
    use ChecksResourcePermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ComponentsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::ComponentsManage;
    }

    protected function deletePermission(): Permission
    {
        return Permission::ComponentsDelete;
    }
}
