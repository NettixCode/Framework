<?php

namespace Nettixcode\App\Traits\Modals;

use Illuminate\Support\Facades\DB;

trait PermissionTrait {

    public function crud_permission_form($permission_name = '', $permission_table_name = '' ,$permission_description = '')
    {
        $excludedTables = config('roper.exclude_table');
        $tableNames  = DB::connection()->getPdo()->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $tableOptions = '';
        foreach ($tableNames as $table) {
            if (!in_array($table, $excludedTables)) {
                $selected = $this->optionSelected($permission_table_name, $table);
                $tableOptions .= '<option value="' . $table . '" ' . $selected . '>' . $table . '</option>';
            }
        }

        $permissionForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="name">Permission Name</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="permission-icon" class="input-group-text"><i class="bx bx-face"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Permission Name" aria-label="Permission Name" aria-describedby="permission-icon" value="'.$permission_name.'" required>
                    </div>
                </div>
            </div>
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="table_name">Tables</label>
                <div class="col-sm-8">
                    <div class="position-relative">
                        <select class="form-control select2" id="table_name" name="table_name" required>
                            <option value=""></option>
                            ' . $tableOptions . '
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="description">Description</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="description-icon" class="input-group-text"><i class="bx bx-captions"></i></span>
                        <input type="text" class="form-control" id="description" name="description" placeholder="Enter Permission Description" aria-label="Permission Description" aria-describedby="description-icon" value="'.$permission_description.'" required>
                    </div>
                </div>
            </div>
        ';

        return '
            <div class="card border-0 shadow-none">
                <div class="card-body">
                    ' . $permissionForm . '
                </div>
            </div>';
    }
}
