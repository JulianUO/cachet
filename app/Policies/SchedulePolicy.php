<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Policies\Concerns\ChecksResourcePermissions;

class SchedulePolicy
{
    use ChecksResourcePermissions;

    protected function viewPermission(): Permission
    {
        return Permission::SchedulesView;
    }

    protected function managePermission(): Permission
    {
        return Permission::SchedulesManage;
    }

    protected function deletePermission(): Permission
    {
        return Permission::SchedulesDelete;
    }
}
