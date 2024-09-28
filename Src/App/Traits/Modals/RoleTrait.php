<?php

namespace Nettixcode\App\Traits\Modals;

trait RoleTrait {

    //ROLE
    public function crud_role_form($permissions, $role_permissions = [], $role_name = '', $role_description = '')
    {
        $disabled = ($role_name == 'admin' || $role_name == 'user') ? 'disabled' : '';
        $roleForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="name">Role Name</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge" '.$disabled.'>
                        <span id="role-icon" class="input-group-text"><i class="bx bx-face"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Role Name" aria-label="Role Name" aria-describedby="role-icon" value="'.$role_name.'" required>
                    </div>
                </div>
            </div>
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="description">Role Description</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge" '.$disabled.'>
                        <span id="description-icon" class="input-group-text"><i class="bx bx-captions"></i></span>
                        <input type="text" class="form-control" id="description" name="description" placeholder="Enter Role Description" aria-label="Role Description" aria-describedby="description-icon" value="'.$role_description.'" required>
                    </div>
                </div>
            </div>
        ';

        $permissionsForm = '
        <div class="divider">
            <div class="divider-text">
                <i class="bx bx-star"></i>
            </div>
        </div>
        <h5 class="mb-6 text-center text-uppercase">Permissions</h5>
        <div class="table-responsive">
            <table class="table table-flush-spacing mb-0">
                <tbody>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">
                            Administrator Access
                            <i class="bx bx-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Allows a full access to the system" data-bs-original-title="Allows a full access to the system"></i>
                        </td>
                        <td>
                          <div class="d-flex justify-content-end">
                            <div class="form-check mb-0">
                              <input class="form-check-input" type="checkbox" id="selectAll">
                              <label class="form-check-label" for="selectAll">
                                Select All
                              </label>
                            </div>
                          </div>
                        </td>
                    </tr>
        ';

        foreach ($permissions as $table => $perms) {
            $permissionsForm .= '<tr><td class="text-nowrap fw-medium text-heading">' . ucfirst($table) . '</td><td>';

            $permissionsForm .= '<div class="d-flex justify-content-end">';

            foreach ($perms as $permission) {
                $isChecked = in_array($permission->id, $role_permissions) ? 'checked' : '';
                $permissionId = 'permission' . $table . $permission->id;
                $permissionsForm .= '
                    <div class="form-check mb-0 me-4">
                        <input class="form-check-input permission-checkbox" type="checkbox" id="' . $permissionId . '" name="permissions[]" value="'.$permission->id.'" '.$isChecked.'>
                        <label class="form-check-label" for="' . $permissionId . '">
                            '.$permission->name.'
                        </label>
                    </div>
                ';
            }
            $permissionsForm .= '</div></td></tr>';
        }

        $permissionsForm .= '
                </tbody>
            </table>
        </div>
        ';

        return '
            <div class="card border-0 shadow-none">
                <div class="card-body">
                    '.$roleForm.'
                    '.$permissionsForm.'
                </div>
            </div>
        ';
    }
    //ROLE
}
