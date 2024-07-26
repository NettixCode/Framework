<?php

namespace Nettixcode\Framework\Libraries\Sources\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Nettixcode\Framework\Libraries\SessionManager;

abstract class BaseModel extends Model
{
    private $data;
    private $dataExist;
    private $operation;
    private $createSession = false;

    protected static function strsecure($value)
    {
        return $value === null ? '' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function checkExist($data, $id = false)
    {
        $instance = new static();
        $query    = DB::table($instance->getTable());
        $cond     = 'AND';
        foreach ($data as $key => $value) {
            if ($key === 'delimeter') {
                $cond = strtoupper($value);
            } else {
                if ($cond === 'AND') {
                    $query = $query->where($key, '=', $value);
                } else {
                    $query = $query->orWhere($key, '=', $value);
                }
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

    public static function edit(array $data)
    {
        $instance = new static();
        $instance->data = $data;
        $instance->operation = 'edit';
        return $instance;
    }

    public function exist($dataExist)
    {
        $this->dataExist = $dataExist;
        return $this;
    }

    public function save($response = true, $createSession = false)
    {
        $this->createSession = $createSession;
    
        $table = $this->getTable();
        $fillable = $this->getFillable();
        $data = array_intersect_key($this->data, array_flip($fillable));
    
        $connection = DB::connection();
        $connection->beginTransaction();
    
        try {
            if ($this->dataExist && self::checkExist($this->dataExist, $this->operation === 'edit' ? ['id' => $data['id']] : false)) {
                $connection->rollBack();
                if ($response) {
                    return response()->json(['report' => 'existed']);
                } else {
                    return ['report' => 'existed'];
                }
            }
    
            if ($this->operation === 'create') {
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
                }
    
                $userRole = $data['user_role'] ?? null;
                unset($data['user_role']);
    
                $record = DB::table($table)->insertGetId($data);
    
                if ($userRole && $table === 'users') {
                    DB::table('user_roles')->insert([
                        'user_id' => $record,
                        'role_id' => $userRole,
                    ]);
                }
    
                $connection->commit();
                if ($response) {
                    return response()->json(['report' => $record ? 'success' : 'error']);
                } else {
                    return ['report' => $record ? 'success' : 'error'];
                }
            }
    
            if ($this->operation === 'edit') {
                $id = $data['id'];
                unset($data['id']);
    
                $newUserRole = $data['user_role'] ?? null;
                unset($data['user_role']);
    
                $currentUserRole = null;
                if ($table === 'users') {
                    $currentUserRole = DB::table('user_roles')->where('user_id', $id)->value('role_id');
                }
    
                $userUpdateResult = DB::table($table)->where('id', $id)->update($data);
                $roleUpdateResult = false;
                if ($table === 'users' && $newUserRole !== null && $newUserRole != $currentUserRole) {
                    $roleUpdateResult = DB::table('user_roles')->where('user_id', $id)->update(['role_id' => $newUserRole]);
                }
    
                // Check if any update actually happened
                if (!$userUpdateResult && !$roleUpdateResult) {
                    throw new \Exception("No changes made to the record.");
                }
                $result = $userUpdateResult || $roleUpdateResult;
                $connection->commit();
    
                if ($this->createSession && SessionManager::get('id') == $id) {
                    foreach ($data as $key => $value) {
                        SessionManager::set($key, ($result ? $value : SessionManager::get($key)));
                    }
                    if (SessionManager::get('incomplete') == true) {
                        SessionManager::set('incomplete', false);
                        setcookie('incomplete', 'false', 0, '/');
                    }
                }
    
                if ($response) {
                    return response()->json(['report' => ($result ? 'success' : 'error')]);
                } else {
                    return ['report' => ($result ? 'success' : 'error')];
                }
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            if ($response) {
                return response()->json(['report' => 'error', 'message' => $e->getMessage()]);
            } else {
                return ['report' => 'error', 'message' => $e->getMessage()];
            }
        }
    }
    
    protected static function remove($data)
    {
        $instance   = new static();
        $table      = $instance->getTable();
        $connection = DB::connection();
        $connection->beginTransaction();
        try {
            $result = DB::table($table)->where('id', $data['id'])->delete();
            $connection->commit();

            return response()->json(['report' => $result ? 'success' : 'error']);
        } catch (\Exception $e) {
            $connection->rollBack();

            return response()->json(['report' => 'error', 'message' => $e->getMessage()]);
        }
    }

    protected static function createFromExcel($data, $insert)
    {
        $instance = new static();
        $table    = $instance->getTable();
        $json     = [];
        if (isset($data['file_name'])) {
            $upload_dir = env('UPLOAD_DIR', 'uploads/');
            $fileName   = $data['file_name'];
            $target     = $upload_dir . $fileName;
            try {
                $inputFileType = IOFactory::identify($target);
                $reader        = IOFactory::createReader($inputFileType);
                $spreadsheet   = $reader->load($target);
                $worksheet     = $spreadsheet->getActiveSheet();

                $row_counts = $worksheet->getHighestRow();

                $totalRows    = 0;
                $existingRows = 0;
                $insertedRows = 0;

                $connection = DB::connection();
                $connection->beginTransaction();

                for ($i = 2; $i <= $row_counts; $i++) {
                    $totalRows++;
                    $row_data = [];
                    foreach ($insert as $key => $columnIndex) {
                        $columnLetter   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                        $cellValue      = $worksheet->getCell($columnLetter . $i)->getValue();
                        $row_data[$key] = self::strsecure($cellValue);
                    }

                    // Cek apakah data sudah ada
                    $existingDataCount = DB::table($table)
                        ->where($row_data)
                        ->count();

                    if ($existingDataCount > 0) {
                        $existingRows++;
                        continue; // Skip if data already exists
                    } else {
                        $insertResult = DB::table($table)->insert($row_data);

                        if ($insertResult) {
                            $insertedRows++;
                        } else {
                            $connection->rollBack();

                            return response()->json([
                                'report'  => 'error',
                                'message' => 'Insert failed',
                            ]);
                        }
                    }
                }

                $connection->commit();

                if ($totalRows == $existingRows) {
                    $json = [
                        'report'  => 'existed',
                        'message' => 'Duplicated .. all data already exist!',
                    ];
                } else {
                    $json = [
                        'report'        => 'success',
                        'inserted_rows' => $insertedRows,
                        'existing_rows' => $existingRows,
                    ];
                }
            } catch (\Exception $e) {
                if (isset($connection)) {
                    $connection->rollBack();
                }

                return response()->json([
                    'report'  => 'error',
                    'message' => $e->getMessage(),
                ]);
            } finally {
                // error_log('Entering finally block');
                // if (file_exists($target)) {
                //     error_log("File exists: $target");
                //     if (!unlink($target)) {
                //         error_log("Failed to delete file: $target");
                //     } else {
                //         error_log("File deleted: $target");
                //     }
                // } else {
                //     error_log("File not found: $target");
                // }
                response()->json($json);
            }
        } else {
            return response()->json([
                'report'  => 'error',
                'message' => 'File not readable, please try again',
            ]);
        }
    }
}
