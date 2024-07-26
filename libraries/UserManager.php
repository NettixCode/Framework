<?php

namespace Nettixcode\Framework\Libraries;

use Illuminate\Support\Facades\DB;
use Nettixcode\Framework\Libraries\Sources\Models\BaseModel;
use Nettixcode\Framework\Libraries\Sources\Models\Role;

class UserManager extends BaseModel
{
    public $timestamps = false;

    protected $table = 'users';

    protected $fillable = [
        'id', 'username', 'password',
    ];

    protected $hidden = [
        'password',
    ];

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

    public static function auth($data = null, $dataSession = null)
    {
        return new class ($data, $dataSession) {
            private $data;
            private $dataSession;

            public function __construct($data, $dataSession)
            {
                $this->data        = $data;
                $this->dataSession = $dataSession;
            }

            public function id()
            {
                return UserManager::id();
            }

            public function login()
            {
                $user = UserManager::login($this->data, $this->dataSession);
                return $user;
            }

            public function logout()
            {
                return UserManager::logout();
            }

            public function password()
            {
                return UserManager::password($this->data);
            }
        };
    }

    protected static function id(){
        if (SessionManager::get('id')){
            return SessionManager::get('id');
        } else {
            return null;
        }
    }

    protected static function login($data, $dataSession)
    {
        $username = $data['username'];
        $password = $data['password'];

        $user = self::where('username', $username)->first();

        if ($user) {
            if (password_verify($password, $user->password)) {
                if ($user->status == 1) {
                    foreach ($user->toArray() as $key => $value) {
                        if ($key != 'password') {
                            SessionManager::set($key, $value);
                        }
                    }
                    foreach ($dataSession as $key => $value) {
                        SessionManager::set($key, $value);
                    }
                    SessionManager::set('user_role', self::role($user->id));
                    SessionManager::set('isLogin', true);

                    return new class ($user) {
                        private $user;

                        private $checkComplete = false;

                        public function __construct($user)
                        {
                            $this->user = $user;
                        }

                        public function checkComplete()
                        {
                            $this->checkComplete = true;

                            return $this;
                        }

                        public function __destruct()
                        {
                            if ($this->checkComplete) {
                                $incomplete = false;
                                foreach ($this->user->toArray() as $key => $value) {
                                    if (empty($value) && $key != 'password') {
                                        $incomplete = true;
                                    }
                                }

                                SessionManager::set('incomplete', $incomplete);

                                if ($incomplete) {
                                    response()->json([
                                        'report'             => 'success',
                                        'incomplete'         => true,
                                        'incomplete_message' => 'Please Complete Your Profile.',
                                        'message'            => 'Login Success',
                                    ]);

                                    return;
                                } else {
                                    response()->json([
                                        'report'     => 'success',
                                        'incomplete' => false,
                                        'message'    => 'Login Success',
                                    ]);

                                    return;
                                }
                            }

                            response()->json([
                                'report'  => 'success',
                                'message' => 'Login Success',
                            ]);
                        }
                    };
                } else {
                    return response()->json([
                        'report'  => 'error',
                        'message' => 'Your Account is Inactive. Contact Administrator to Activate. Thanks. :)',
                    ]);
                }
            } else {
                return response()->json([
                    'report'  => 'error',
                    'message' => 'Wrong Password!',
                ]);
            }
        } else {
            return response()->json([
                'report'  => 'error',
                'message' => 'Username Not Found!',
            ]);
        }
    }

    protected static function logout()
    {
        SessionManager::destroy();

        return new class () {
            private $redirectUrl = '/';

            public function redirect($url = '/')
            {
                $this->redirectUrl = $url;

                return $this;
            }

            public function __destruct()
            {
                if ($this->redirectUrl) {
                    header("Location: $this->redirectUrl");
                    exit();
                }
            }
        };
    }

    public static function password($data)
    {
        return new class ($data) {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function change()
            {
                $userId      = $this->data['id'];
                $oldPassword = $this->data['old_password'] ?? null;
                $newPassword = $this->data['new_password'];

                if (!$this->password_valid($newPassword)) {
                    return response()->json(['report' => 'error', 'message' => 'Password does not meet the requirements.']);
                }

                $user = UserManager::where('id', $userId)->first();
                if (!$user) {
                    return response()->json(['report' => 'error', 'message' => 'User not found.']);
                }

                $newPassHash = password_hash($newPassword, PASSWORD_BCRYPT);

                if ($this->check_roles($userId)) {
                    return $this->handle_checked_roles($user, $oldPassword, $newPassword, $newPassHash);
                } elseif (UserManager::has('role', 'admin') && UserManager::role($userId) != 1) {
                    return $this->update_password($userId, $newPassHash);
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Unauthorized action.']);
                }
            }

            private function check_roles($userId)
            {
                return (UserManager::has('role', 'admin') && UserManager::role($userId) == 1) || UserManager::has('role', 'user');
            }

            private function handle_checked_roles($user, $oldPassword, $newPassword, $newPassHash)
            {
                if (password_verify($oldPassword, $user->password)) {
                    if ($oldPassword != $newPassword) {
                        return $this->update_password($user->id, $newPassHash);
                    } else {
                        return response()->json(['report' => 'error', 'message' => 'Password tidak boleh sama dengan yang lama!']);
                    }
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Silahkan input user_pass lama anda untuk mengubah user_pass. Terima kasih. :)']);
                }
            }

            private function update_password($userId, $newPassHash)
            {
                $result = UserManager::where('id', $userId)->update(['password' => $newPassHash]);

                return $this->generateResponse($result, $userId);
            }

            private function generateResponse($result, $userId)
            {
                if ($result) {
                    SessionManager::get('id') == $userId ? SessionManager::set('isLogin', false) : SessionManager::set('isLogin', true);

                    return response()->json(['report' => 'success', 'id' => $userId]);
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Failed to update password.']);
                }
            }

            private function password_valid($password)
            {
                // Contoh validasi: panjang minimal 8 karakter, harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial
                return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
            }
        };
    }
}
