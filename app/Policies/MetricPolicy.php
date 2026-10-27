<?php

namespace App\Policies;

use App\Auth\Permission;
use App\Policies\Concerns\ChecksResourcePermissions;

class MetricPolicy
{
    use ChecksResourcePermissions;

    protected function viewPermission(): Permission
    {
        return Permission::MetricsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::MetricsManage;
    }

    protected function deletePermission(): Permission
    {
        return Permission::MetricsDelete;
    }
}
