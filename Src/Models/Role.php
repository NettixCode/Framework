<?php

namespace Nettixcode\Framework\Models;

// use System\Helpers\Helper;

class Role extends BaseModel
{

    protected $table = 'roles';

    protected $fillable = [
        'id', 'name', 'description',
    ];

    public function users()
    {
        return $this->belongsToMany(UserManager::class, 'user_roles', 'role_id', 'user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public static function datatable()
    {
        $roles  = self::orderBy('id', 'ASC')->get();
        $result = [];
        $index  = 0;

        foreach ($roles as $role) {
            $index++;
            $result[] = [
                $index,
                $role->name,
                $role->description,
                tableRoleOptions($role->id, $role->name),
            ];
        }

        return $result;
    }
}
