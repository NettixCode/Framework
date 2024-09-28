<?php

use Nettixcode\Facades\User;

if (!function_exists('nettixcode_path')){
    function nettixcode_path($path = ''){
        return base_path('vendor/nettixcode/framework/src/').$path;
    }
}

if (!function_exists('userStatus')) {
    function userStatus($str, $custom = false)
    {
        switch($str) {
            case 2:
                return $custom ? '<span class="label text-danger d-flex"><span class="dot-label bg-danger me-1"></span>Inactive</span>' : 'Inactive';
            case 1:
                return $custom ? '<span class="label text-success d-flex"><span class="dot-label bg-success me-1"></span>Active</span>' : 'Active';
        }
    }
}

if (!function_exists('tableRoleOptions')) {
    function tableRoleOptions($id, $name)
    {
        if ($name === 'admin' || $name === 'user') {
            return
                '<a role="button" class="btn btn-icon btn-sm btn-info" data-id="' . $id . '" title="Edit Role" data-actions="edit-role"><i class="bx bx-edit bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a class="btn btn-icon btn-sm btn-light disabled"><i class="bx bx-trash bx-sm ms-1 me-1"></i>
                </a>';
        } else {
            return
                '<a role="button" class="btn btn-icon btn-sm btn-info" data-id="' . $id . '" title="Edit Role" data-actions="edit-role"><i class="bx bx-edit bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-danger" data-id="' . $id . '" title="Delete Role" data-actions="delete-role"><i class="bx bx-trash bx-sm ms-1 me-1 text-white"></i>
                </a>';
        }
    }
}

if (!function_exists('tablePermissionOptions')) {
    function tablePermissionOptions($id, $name)
    {
        if (str_contains($name,'create') ||  str_contains($name,'read') || str_contains($name,'update') || str_contains($name,'delete')) {
            return
                '<a class="btn btn-icon btn-sm btn-light disabled"><i class="bx bx-edit bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a class="btn btn-icon btn-sm btn-light disabled"><i class="bx bx-trash bx-sm ms-1 me-1"></i>
                </a>';
        } else {
            return
                '<a role="button" class="btn btn-icon btn-sm btn-info" data-id="' . $id . '" title="Edit Permission" data-actions="edit-permission"><i class="bx bx-edit bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-danger" data-id="' . $id . '" title="Delete Permission" data-actions="delete-permission"><i class="bx bx-trash bx-sm ms-1 me-1 text-white"></i>
                </a>';
        }
    }
}

if (!function_exists('rolePermissionList')) {
    function rolePermissionList($permissionId) {
        // Kumpulan kelas badge
        $badgeClasses = [
            'bg-label-primary',
            'bg-label-secondary',
            'bg-label-success',
            'bg-label-danger',
            'bg-label-warning',
            'bg-label-info',
            'bg-label-dark'
        ];

        $roles = DB::table('role_permissions')
            ->where('permission_id', $permissionId)
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->select('roles.name')
            ->pluck('roles.name');

        static $roleBadgeMap = [];

        foreach ($roles as $role) {
            if (!isset($roleBadgeMap[$role])) {
                shuffle($badgeClasses);
                $roleBadgeMap[$role] = $badgeClasses[array_rand($badgeClasses)];
            }
        }

        $badges = $roles->map(function($role) use ($roleBadgeMap) {
            return '<span class="text-nowrap"><a href="'.route('roles').'"><span class="badge ' . $roleBadgeMap[$role] . ' me-4">' . e($role) . '</span></a></span>';
        });

        return $badges->implode(' ');
    }
}

if (!function_exists('tableUserOptions')) {
    function tableUserOptions($id)
    {
        if (Auth::user()->isAdmin()) {
            return '
                <a href="'.(($id == Auth::id()) ? route('account') : route('account', ['id' => $id])).'" role="button" class="btn btn-icon btn-sm btn-success"><i class="bx bx-show bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-info" data-id="' . $id . '" title="Edit Users" data-actions="edit-users"><i class="bx bx-edit bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-primary" data-id="' . $id . '" title="Edit Image" data-actions="edit-image"><i class="bx bx-image bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-warning" data-id="' . $id . '" title="Edit Pass" data-actions="edit-pass"><i class="bx bx-key bx-sm ms-1 me-1 text-white"></i>
                </a>
                <a role="button" class="btn btn-icon btn-sm btn-danger" data-id="' . $id . '" title="Delete Users" data-actions="delete-users"><i class="bx bx-trash bx-sm ms-1 me-1 text-white"></i>
                </a>';
        } else {
            return '
                <a role="button" class="btn btn-icon btn-sm btn-danger" data-actions="not-allowed"><i class="bx bx-lock bx-sm ms-1 me-1 text-white"></i>
                </a>';
        }
    }
}
