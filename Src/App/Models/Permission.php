<?php

namespace Nettixcode\App\Models;

use Illuminate\Support\Facades\DB;

class Permission extends BaseModel
{
    protected $table = 'permissions';

    protected $fillable = [
        'id', 'name', 'table_name', 'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public static function datatable()
    {
        $permissions = self::orderBy('id', 'ASC')->get();
        $result = [];
        $index  = 0;

        foreach ($permissions as $permission) {
            $index++;
            $result[] = [
                $index,
                $permission->name,
                rolePermissionList($permission->id),
                $permission->table_name,
                $permission->description,
                tablePermissionOptions($permission->id, $permission->name),
            ];
        }

        return $result;
    }
}
