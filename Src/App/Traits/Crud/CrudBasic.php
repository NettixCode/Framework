<?php

namespace Nettixcode\App\Traits\Crud;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CrudBasic
{
    private $data;
    private $dataExist;
    private $operation;
    private $createSession = false;
    private $permissionCalled = false;
    private $permissions = []; // Menambahkan property untuk permissions

    private static function checkExist($data, $id = false)
    {
        $instance = new static();
        $query    = DB::table($instance->getTable());
        $cond     = 'AND';

        if (isset($data['delimeter'])) {
            $cond = strtoupper($data['delimeter']);
            unset($data['delimeter']);
        }

        foreach ($data as $key => $value) {
            if ($cond === 'AND') {
                $query = $query->where($key, '=', $value);
            } else {
                $query = $query->orWhere($key, '=', $value);
            }
        }

        if ($id) {
            foreach ($id as $key => $value) {
                $query = $query->whereNotIn($key, (array) $value);
            }
        }

        return $query->count();
    }

    public static function isDataExist($data, $id = false)
    {
        return self::checkExist($data, $id) > 0;
    }

    public static function create(array $data)
    {
        $instance = new static();
        $instance->data = $data;
        $instance->operation = 'create';
        return $instance;
    }

    public function exist($dataExist)
    {
        $this->dataExist = $dataExist;
        return $this;
    }

    public function withPermissions($permissions)
    {
        $this->permissionCalled = true;
        $this->permissions = $permissions;
        return $this;
    }

    public static function edit(array $data)
    {
        $instance = new static();
        $instance->data = $data;
        $instance->operation = 'edit';
        return $instance;
    }

    public function save($response = true, $createSession = false)
    {
        $this->createSession = $createSession;

        $table = $this->getTable();
        $fillable = $this->getFillable();
        $data = array_intersect_key($this->data, array_flip($fillable));

        DB::beginTransaction();

        try {
            if ($this->dataExist && self::checkExist($this->dataExist, $this->operation === 'edit' ? ['id' => $data['id']] : false)) {
                DB::rollBack();
                return $response ? response()->json(['report' => 'existed']) : ['report' => 'existed'];
            }

            if ($this->operation === 'create') {
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
                }

                // Check and add created_at if the column exists
                if (Schema::hasColumn($table, 'created_at') && !isset($data['created_at'])) {
                    $data['created_at'] = now();
                }

                $userRole = $data['user_role'] ?? null;
                unset($data['user_role']);

                $record = DB::table($table)->insertGetId($data);

                if (!$record) {
                    DB::rollBack();
                    return $response ? response()->json(['report' => 'error', 'message' => 'Failed to create user']) : ['report' => 'error'];
                }

                if ($userRole && $table === 'users') {
                    foreach ($userRole as $roleId) {
                        DB::table('user_roles')->insert([
                            'user_id' => $record,
                            'role_id' => $roleId,
                        ]);
                    }
                }

                // Simpan permissions
                if (!empty($this->permissions) && $this->permissionCalled) {
                    foreach ($this->permissions as $permissionId) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $record,
                            'permission_id' => $permissionId
                        ]);
                    }
                }

                \Debugbar::info('messages', ($record ? 'success' : 'error').' '.$this->operation.' to '.$table.' with data '.json_encode($data));

                DB::commit();
                return $response ? response()->json(['report' => 'success']) : ['report' => 'success'];
            }

            if ($this->operation === 'edit') {
                if (!is_array($this->data) || empty($this->data)) {
                    throw new \Exception("Data untuk update tidak valid atau kosong.");
                }

                $id = $this->data['id'] ?? null;
                if (!$id) {
                    throw new \Exception("ID tidak ditemukan dalam data.");
                }

                unset($this->data['id']);
                $newUserRoles = $this->data['user_role'] ?? null; // Mengambil user_roles sebagai array
                unset($this->data['user_role']);

                $data = array_intersect_key($this->data, array_flip($this->getFillable()));

                // Check and add updated_at if the column exists
                if (Schema::hasColumn($table, 'updated_at') && !isset($data['updated_at'])) {
                    $data['updated_at'] = now();
                }

                if (empty($data) && (empty($newUserRoles) || $table !== 'users')) {
                    throw new \Exception("Tidak ada data yang bisa diupdate.");
                }

                // Update user data
                $userUpdateResult = DB::table($table)->where('id', $id)->update($data);

                // Update roles jika ada perubahan
                $roleUpdateResult = false;
                if ($table === 'users' && $newUserRoles !== null) {
                    // Dapatkan role saat ini dari tabel user_roles
                    $currentUserRoles = DB::table('user_roles')->where('user_id', $id)->pluck('role_id')->toArray();

                    // Jika role baru berbeda dari yang saat ini, lakukan update
                    if (array_diff($currentUserRoles, $newUserRoles) || array_diff($newUserRoles, $currentUserRoles)) {
                        // Hapus existing roles
                        DB::table('user_roles')->where('user_id', $id)->delete();

                        // Insert new roles
                        foreach ($newUserRoles as $roleId) {
                            DB::table('user_roles')->insert([
                                'user_id' => $id,
                                'role_id' => $roleId,
                            ]);
                        }
                        $roleUpdateResult = true; // Tandai bahwa role telah diperbarui
                    }
                }

                // Update permissions
                $permissionsUpdated = false;
                if (!empty($this->permissions) && $this->permissionCalled) {
                    // Hapus existing permissions
                    DB::table('role_permissions')->where('role_id', $id)->delete();

                    // Simpan permissions baru
                    foreach ($this->permissions as $permissionId) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $id,
                            'permission_id' => $permissionId
                        ]);
                    }

                    $permissionsUpdated = true;
                } else {
                    if ($this->permissionCalled){
                        // Jika permissions kosong, tetap hapus semua permission yang ada
                        DB::table('role_permissions')->where('role_id', $id)->delete();
                        $permissionsUpdated = true; // Tetapkan flag sebagai true karena permissions dihapus
                    }
                }

                if (!$userUpdateResult && !$roleUpdateResult && !$permissionsUpdated) {
                    throw new \Exception("Tidak ada perubahan yang dilakukan pada record.");
                }

                DB::commit();

                return $response ? response()->json(['report' => 'success']) : ['report' => 'success'];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::alert('exceptions: '.$e);
            return $response ? response()->json(['report' => 'error', 'message' => $e->getMessage()]) : ['report' => 'error'];
        }
    }

    public static function remove($data)
    {
        DB::beginTransaction();

        try {
            $instance = static::findOrFail($data['id']);
            $result = $instance->delete();

            DB::commit();
            return response()->json(['report' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::alert('exceptions: '.$e);
            return response()->json(['report' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
