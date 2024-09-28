<?php

namespace Nettixcode\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Nettixcode\App\Models\Permission;
use Nettixcode\App\Models\Role;
use Nettixcode\App\Traits\Crud\CrudBasic;

class User extends Authenticatable
{
    use CrudBasic;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'username', 'password', 'first_name', 'last_name', 'phone', 'email', 'status', 'tahun_anggaran', 'user_role', 'profile_picture', 'old_password', 'new_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function setFillable(array $fields)
    {
        $instance = new static();
        $instance->fillable = array_merge($instance->fillable, $fields);
    }

    public static function login(array $data, array $dataSession)
    {
        $username = $data['username'];
        $password = $data['password'];

        $user = self::where('username', $username)->first();

        if ($user){
            if (Auth::attempt(['username' => $username, 'password' => $password])) {
                if ($user->status == 1) {
                    Session::regenerate();
                    // foreach ($user->toArray() as $key => $value) {
                    //     if ($key != 'password' && $key != 'created_at' && $key != 'updated_at') {
                    //         Session::put($key, $value);
                    //     }
                    // }
                    foreach ($dataSession as $key => $value) {
                        Session::put($key, $value);
                    }
                    // Session::put('user_role', self::role($user->id));
                    Session::put('isLogin', true);

                    return new class($user) {
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

                        public function sendResponse()
                        {
                            // Generate JWT token or perform any other action if needed
                            // Example JWT token generation (replace with actual logic):
                            // generateJwtToken();

                            if ($this->checkComplete) {
                                $incomplete = false;
                                foreach ($this->user->toArray() as $key => $value) {
                                    if (empty($value) && $key != 'password') {
                                        $incomplete = true;
                                    }
                                }

                                Session::put('incomplete', $incomplete);
                                Session::put('SYSTEM_UP',microtime(true));

                                if ($incomplete) {
                                    return response()->json([
                                        'report'             => 'success',
                                        'incomplete'         => true,
                                        'incomplete_message' => 'Please Complete Your Profile.',
                                        'message'            => 'Login Success',
                                    ]);
                                } else {
                                    return response()->json([
                                        'report'     => 'success',
                                        'incomplete' => false,
                                        'message'    => 'Login Success',
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'report'  => 'success',
                                    'message' => 'Login Success',
                                ]);
                            }
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

    public function logout()
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        Session::flush();

        return new class () {
            private $redirectUrl = '/';

            public function redirect($url = '/')
            {
                $this->redirectUrl = $url;

                return $this;
            }

            public function __destruct()
            {
                redirect()->Route($this->redirectUrl);
            }
        };
    }

    public static function Password($data)
    {
        return new class ($data) {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function change()
            {
                $userId = $this->data['id'];
                $oldPassword = $this->data['old_password'] ?? null;
                $newPassword = $this->data['new_password'];

                if (!$this->passwordValid($newPassword)) {
                    return Response::json(['report' => 'error', 'message' => 'Password does not meet the requirements.']);
                }

                $user = User::find($userId);
                if (!$user) {
                    return Response::json(['report' => 'error', 'message' => 'User not found.']);
                }

                $newPassHash = Hash::make($newPassword);

                if ($this->checkRoles($userId)) {
                    return $this->handleCheckedRoles($user, $oldPassword, $newPassword, $newPassHash);
                } elseif (Auth::user()->isAdmin() && User::role($userId) != 1) {
                    return $this->updatePassword($userId, $newPassHash);
                } else {
                    return Response::json(['report' => 'error', 'message' => 'Unauthorized action.']);
                }
            }

            private function checkRoles($userId)
            {
                return (Auth::user()->isAdmin() && User::role($userId) == 1) || User::hasRoles('user');
            }

            private function handleCheckedRoles($user, $oldPassword, $newPassword, $newPassHash)
            {
                if (Hash::check($oldPassword, $user->password)) {
                    if ($oldPassword != $newPassword) {
                        return $this->updatePassword($user->id, $newPassHash);
                    } else {
                        return Response::json(['report' => 'error', 'message' => 'Password cannot be the same as the old one!']);
                    }
                } else {
                    return Response::json(['report' => 'error', 'message' => 'Please enter your old password to change the password. Thank you. :)']);
                }
            }

            private function updatePassword($userId, $newPassHash)
            {
                $result = User::where('id', $userId)->update(['password' => $newPassHash]);

                return $this->generateResponse($result, $userId);
            }

            private function generateResponse($result, $userId)
            {
                if ($result) {
                    Session::put('isLogin', Auth::id() == $userId ? false : true);
                    return Response::json(['report' => 'success', 'id' => $userId]);
                } else {
                    return Response::json(['report' => 'error', 'message' => 'Failed to update password.']);
                }
            }

            private function passwordValid($password)
            {
                // Example validation: minimum 8 characters, must contain uppercase, lowercase, number, and special character
                return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\-_.])[A-Za-z\d!@#$%^&*\-_.]{8,}$/', $password);
            }
        };
    }

    public static function profile_picture($data, $image_column = 'profile_picture')
    {
        $json = [];

        if (isset($data['file_name'])) {
            $upload_dir = env('UPLOAD_IMAGE_DIR', 'img/users/');
            $fileName   = $data['file_name'];
            $target     = $upload_dir . $fileName;

            $userId = $data['id'];

            $link_target = '/' . $target; // Adjusted link target based on public_path

            // Update database
            $result = self::where('id', $userId)->update([$image_column => $link_target]);

            if ($result) {
                $json['report']      = 'success';
                $json[$image_column] = $link_target;

                // Update session if needed
                // if (Auth::id() == $userId) {
                //     session()->put($image_column, $link_target);
                // }
            } else {
                $json['report']  = 'error';
                $json['message'] = 'Failed to update image in database';
            }
        } else {
            $json['report']  = 'error';
            $json['message'] = 'File not readable, please try again';
        }

        return response()->json($json);
    }

    public static function role($id = null, $field = 'id')
    {
        $user = $id ? self::with('roles')->find($id) : (Auth::check() ? Auth::user() : null);

        $role = $user ? $user->roles->first() : null;

        return $role ? $role->{$field} : null;
    }

    public static function roleName($id = null)
    {
        $user = $id ? self::with('roles')->find($id) : (Auth::check() ? Auth::user() : null);

        $role = $user ? $user->roles->first() : null;

        return $role ? $role->{'name'} : null;
    }

    public static function has($type, $name, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return false;
        }

        // Eager load the 'roles' and 'permissions' relationship to reduce queries
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function rolesCount()
    {
        return $this->roles()->count();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->using('user_roles')
            ->where('user_id', $this->id);
    }

    public function permissionsCount()
    {
        return $this->permissions()->count();
    }

    public static function hasRoles($roleName, $userId = null)
    {
        // Gunakan Auth::id() jika userId tidak disediakan
        $userId = $userId ?? Auth::id();

        // Jika tidak ada userId yang valid, kembalikan false
        if (!$userId) {
            return false;
        }

        // Langsung cek apakah user memiliki role yang dimaksud
        return self::where('id', $userId)
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->exists();
    }

    public static function hasPermissions($permissionName, $userId = null)
    {
        // Gunakan Auth::id() jika userId tidak disediakan
        $userId = $userId ?? Auth::id();

        // Jika tidak ada userId yang valid, kembalikan false
        if (!$userId) {
            return false;
        }

        // Langsung cek apakah user memiliki permission yang dimaksud melalui relasi roles -> permissions
        return self::where('id', $userId)
            ->whereHas('roles.permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();
    }

    public function hasRole($roleName, $userId = null)
    {
        // Gunakan Auth::id() jika userId tidak disediakan
        $userId = $userId ?? Auth::id();

        // Jika tidak ada userId yang valid, kembalikan false
        if (!$userId) {
            return false;
        }

        // Langsung cek apakah user memiliki role yang dimaksud
        return self::where('id', $userId)
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->exists();
    }

    public function hasPermission($permissionName, $userId = null)
    {
        // Gunakan Auth::id() jika userId tidak disediakan
        $userId = $userId ?? Auth::id();

        // Jika tidak ada userId yang valid, kembalikan false
        if (!$userId) {
            return false;
        }

        // Langsung cek apakah user memiliki permission yang dimaksud melalui relasi roles -> permissions
        return self::where('id', $userId)
            ->whereHas('roles.permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();
    }

    public function assignRole($role)
    {
        if (is_numeric($role)) {
            $roleId = $role;
        } else {
            $roleId = Role::where('name', $role)->firstOrFail()->id;
        }
        $this->roles()->attach($roleId);

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

    public function isAdmin()
    {
        return $this->hasRole(config('roper.administrator.name'));
    }
}
