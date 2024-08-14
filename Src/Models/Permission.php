<?php

namespace Nettixcode\Framework\Models;

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

        // Daftar tabel yang ingin dikecualikan
        $excludedTables = [
            'migrations',
            'permissions',
            'role_permissions',
            'roles',
            'seeders',
            'user_roles',
        ];

        // Mengambil semua tabel dari database
        $tableNames  = [];
        $tablesQuery = DB::connection()->getPdo()->query('SHOW TABLES');
        while ($table = $tablesQuery->fetch()) {
            $tableNames[] = array_values($table)[0];
        }

        // Mengambil semua roles dari database
        $roles = DB::table('roles')->select('id', 'name')->get();

        foreach ($tableNames as $tableName) {
            // Melakukan filter untuk tabel yang dikecualikan
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
        // Get all permissions for the table
        $allPermissions = DB::table('permissions')
            ->where('permissions.table_name', $tableName)
            ->select('permissions.id', 'permissions.name')
            ->get();

        // Get role-specific permissions for the table
        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.table_name', $tableName)
            ->select('permissions.name')
            ->pluck('permissions.name')
            ->toArray();

        $permissionsHtml = '';
        foreach ($allPermissions as $permission) {
            $badgeClass = in_array($permission->name, $rolePermissions) ? 'bg-primary' : 'bg-light';
            $permissionsHtml .= "<span role=\"button\" class=\"badge $badgeClass\" data-role=\"{$roleId}\" data-permission=\"{$permission->id}\" data-table=\"$tableName\">{$permission->name}</span> ";
        }

        return $permissionsHtml;
    }

    public static function updates($changes)
    {
        // Inisialisasi variabel untuk melacak keberhasilan semua operasi
        $allSuccess   = true;
        $errorMessage = '';

        foreach ($changes as $change) {
            $roleId       = $change['role_id'];
            $permissionId = $change['permission_id'];
            $state        = $change['state'];

            try {
                if ($state == 'active') {
                    // Periksa apakah data sudah ada sebelum melakukan updateOrInsert
                    $exists = DB::table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        // Data belum ada, lakukan insert
                        $result = DB::table('role_permissions')
                            ->insert([
                                'role_id'       => $roleId,
                                'permission_id' => $permissionId,
                            ]);
                    } else {
                        // Data sudah ada, lakukan update
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
                    // Periksa apakah data ada sebelum melakukan delete
                    $exists = DB::table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if ($exists) {
                        // Data ada, lakukan delete
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
                        // Data tidak ada, lanjutkan ke perubahan berikutnya
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
