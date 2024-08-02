<?php

namespace Nettixcode\Framework\Libraries;

use Illuminate\Support\Facades\DB;
use Nettixcode\Framework\Models\BaseModel;
use Nettixcode\Framework\Models\Role;
use Nettixcode\Framework\Libraries\SessionManager;
use Illuminate\Http\JsonResponse;

class UserManager extends BaseModel
{
    private static $instance = null;
    public $timestamps = false;
    protected $table = 'users';

    protected $fillable = [
        'id', 'username', 'password',
    ];

    protected $hidden = [
        'password',
    ];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function encrypt_password($value)
    {
        return password_hash($user_pass, PASSWORD_BCRYPT);
    }

    protected function decrypt_password($user_pass, $hash)
    {
        return password_verify($user_pass, $hash) ? $user_pass : '';
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public static function allUsers($column = null, $direction = 'asc')
    {
        $query = self::query();
        if ($column) {
            $query->orderBy($column, $direction);
        }

        return $query->get();
    }

    public function __call($method, $parameters)
    {
        if ($method == 'allUsers') {
            return $this->allUsers(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        if ($method == 'allUsers') {
            return (new static())->allUsers(...$parameters);
        }

        return parent::__callStatic($method, $parameters);
    }

    public static function role($id, $field = 'id')
    {
        $role = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $id)
            ->select('roles.*')
            ->first();

        return $role ? $role->{$field} : null;
    }

    public function assignRole($role)
    {
        if (is_numeric($role)) {
            $roleId = $role;
        } else {
            $roleId = Role::where('name', $role)->firstOrFail()->id;
        }
        $this->roles()->attach($roleId);

        // Assign permissions dari role ke user
        $permissions = Role::find($roleId)->permissions;
        foreach ($permissions as $permission) {
            $this->permissions()->attach($permission);
        }
    }

    public function removeRole($role)
    {
        if (is_numeric($role)) {
            $roleId = $role;
        } else {
            $roleId = Role::where('name', $role)->firstOrFail()->id;
        }
        $this->roles()->detach($roleId);
    }

    public function revokeAllRoles()
    {
        $this->roles()->detach();
    }

    public function rolesCount()
    {
        return $this->roles()->count();
    }

    public static function has($type, $name, $userId = null)
    {
        $userId = $userId ?? SessionManager::get('id');
        if (!$userId) {
            return false;
        }

        $user = self::with('roles.permissions')->find($userId);
        if (!$user) {
            return false;
        }

        if ($type === 'role') {
            return $user->roles()->where('name', $name)->exists();
        } elseif ($type === 'permission') {
            return $user->roles()->whereHas('permissions', function ($query) use ($name) {
                $query->where('name', $name);
            })->exists();
        }

        return false;
    }

    public function permissions()
    {
        return DB::table('permissions')
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $this->id)
            ->pluck('permissions.name')
            ->unique();
    }

    public function assignPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $this->permissions()->attach($permission);
        }
    }

    public function removePermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $this->permissions()->detach($permission);
        }
    }

    public function revokeAllPermissions()
    {
        $this->permissions()->detach();
    }

    public function permissionsCount()
    {
        return $this->permissions()->count();
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
    }

    public function deactivate()
    {
        $this->status = 'inactive';
        $this->save();
    }

    public function isActive()
    {
        return $this->status == 'active';
    }

}
