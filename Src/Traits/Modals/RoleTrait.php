<?php

namespace Nettixcode\Framework\Traits\Modals;

trait RoleTrait {

    //ROLE
    public function crud_role_form($role_name = '', $role_description = '')
    {
        $roleForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="name">Role Name</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="role-icon" class="input-group-text"><i class="bx bx-face"></i></span>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Role Name" aria-label="Role Name" aria-describedby="role-icon" required>
                    </div>
                </div>
            </div>
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="description">Role Description</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="description-icon" class="input-group-text"><i class="bx bx-captions"></i></span>
                        <input type="text" class="form-control" id="description" name="description" placeholder="Enter Role Description" aria-label="Role Description" aria-describedby="description-icon" required>
                    </div>
                </div>
            </div>
        ';

        return '
            <div class="card">
                <div class="card-body">
                    '.$roleForm.'
                </div>
            </div>
        ';
    }
    //ROLE
}