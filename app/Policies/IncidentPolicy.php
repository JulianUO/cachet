<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Policies\Concerns\ChecksResourcePermissions;

class IncidentPolicy
{
    use ChecksResourcePermissions;

    protected function viewPermission(): Permission
    {
        return Permission::IncidentsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::IncidentsManage;
    }

    protected function deletePermission(): Permission
    {
        return Permission::IncidentsDelete;
    }
}
