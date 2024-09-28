<?php

namespace Nettixcode\App\Models;

use Illuminate\Support\Facades\DB;

class Permission extends BaseModel
{
    protected $table = 'permissions';

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public static function datatable()
    {
        $result = [];
        $index  = 0;

        // Exclude certain tables from the permissions list
        $excludedTables = [
            'cache',
            'cache_locks',
            'migrations',
            'password_reset_tokens',
            'permissions',
            'role_permissions',
            'roles',
            'seeders',
            'sessions',
            'user_roles',
        ];

        // Get table names from the database
        $tableNames  = DB::connection()->getPdo()->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $roles = DB::table('roles')->select('id', 'name')->get();

        // Loop over tables to prepare permission data
        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $excludedTables)) {
                continue;
            }

            $index++;
            $row = [
                'index'      => $index,
                'table_name' => $tableName,
            ];

            foreach ($roles as $role) {
                $row['role_' . $role->id] = self::getPermissionsHtml($tableName, $role->id);
            }

            $result[] = $row;
        }

        return [
            'data'  => $result,
            'roles' => $roles,
        ];
    }

    public static function getPermissionsHtml($tableName, $roleId)
    {
        // Retrieve permissions for the table and role
        $allPermissions = DB::table('permissions')
            ->where('permissions.table_name', $tableName)
            ->select('permissions.id', 'permissions.name')
            ->get();

        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.table_name', $tableName)
            ->select('permissions.name')
            ->pluck('permissions.name')
            ->toArray();

        // Create the HTML for permissions
        $permissionsHtml = '';
        foreach ($allPermissions as $permission) {
            $badgeClass = in_array($permission->name, $rolePermissions) ? 'bg-primary' : 'bg-light';
            $permissionList = str_replace('-'.$tableName, '', $permission->name);
            $permissionsHtml .= "<span role=\"button\" class=\"badge $badgeClass\" data-role=\"{$roleId}\" data-permission=\"{$permission->id}\" data-table=\"$tableName\">{$permissionList}</span> ";
        }

        return '<div class="demo-inline-spacing">' . $permissionsHtml . '</div>';
    }

    public static function updates($changes)
    {
        $allSuccess   = true;
        $errorMessage = '';

        foreach ($changes as $change) {
            $roleId       = $change['role_id'];
            $permissionId = $change['permission_id'];
            $state        = $change['state'];

            try {
                if ($state == 'active') {
                    $exists = DB::table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        $result = DB::table('role_permissions')
                            ->insert([
                                'role_id'       => $roleId,
                                'permission_id' => $permissionId,
                            ]);
                    } else {
                        $result = DB::table('role_permissions')
                            ->where('role_id', $roleId)
                            ->where('permission_id', $permissionId)
                            ->update([
                                'role_id'       => $roleId,
                                'permission_id' => $permissionId,
                            ]);
                    }

                    if ($result === false) {
                        $allSuccess   = false;
                        $errorMessage = "Failed to insert/update role_permission for role_id $roleId and permission_id $permissionId";
                        break;
                    }
                } else {
                    $exists = DB::table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if ($exists) {
                        $deleteCount = DB::table('role_permissions')
                            ->where('role_id', $roleId)
                            ->where('permission_id', $permissionId)
                            ->delete();

                        if ($deleteCount === 0) {
                            $allSuccess   = false;
                            $errorMessage = "Failed to delete role_permission for role_id $roleId and permission_id $permissionId";
                            break;
                        }
                    } else {
                        continue;
                    }
                }
            } catch (\Exception $e) {
                $allSuccess   = false;
                $errorMessage = $e->getMessage();
                break;
            }
        }

        if ($allSuccess) {
            return ['report' => 'success'];
        } else {
            return ['report' => 'error', 'message' => $errorMessage];
        }
    }
}
