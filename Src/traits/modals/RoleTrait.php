<?php

namespace Nettixcode\Framework\Traits\Modals;

trait RoleTrait {

    //ROLE
    public function crud_role_form($role_name = '', $role_description = '')
    {
        $roleForm = '
            <div class="row row-xs align-items-center mg-b-20">
                <div class="col-md-4">
                    <label class="mg-b-0">Role Name <span class="tx-danger">*</span></label>
                </div>
                <div class="col-md-8 mg-t-5 mg-md-t-0">
                    <input class="form-control" placeholder="Role Name" type="text" id="name" name="name" value="' . $role_name . '" required>
                </div>
            </div>
            <div class="row row-xs align-items-center mg-b-20">
                <div class="col-md-4">
                    <label class="mg-b-0">Role Description <span class="tx-danger">*</span></label>
                </div>
                <div class="col-md-8 mg-t-5 mg-md-t-0">
                    <input class="form-control" placeholder="Role Description" type="text" id="description" name="description" value="' . $role_description . '" required>
                </div>
            </div>
        ';

        return $roleForm;
    }
    //ROLE
}