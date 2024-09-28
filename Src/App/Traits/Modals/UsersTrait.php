<?php

namespace Nettixcode\App\Traits\Modals;

use Nettixcode\Facades\User;
use Illuminate\Support\Facades\Auth;

trait UsersTrait {

    // USER
    public function crud_users_form(
        $roles = [],
        $username = '',
        $first_name = '',
        $last_name = '',
        $phone = '',
        $email = '',
        $status = '',
        $user_role = [],
        $required = ''
    )
    {
        $userForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="username">Username</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="username-icon" class="input-group-text"><i class="bx bx-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your Username" aria-label="Username" aria-describedby="username-icon" value="' . $username . '" required>
                    </div>
                </div>
            </div>';

        if ($username == '' &&
            $first_name == '' &&
            $last_name == '' &&
            $phone == '' &&
            $email == '' &&
            $status == '' &&
            $user_role == [] &&
            $required == ''
        )
        {
            $userForm .= '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="password">Password</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="password-icon" class="input-group-text"><i class="bx bx-key"></i></span>
                        <input type="text" class="form-control" id="password" name="password" placeholder="Enter your Password" aria-label="Password" aria-describedby="password-icon" required>
                        <span id="generate-password" class="input-group-text bg-primary" role="button"><i class="bx bx-refresh"></i></span>
                    </div>
                </div>
            </div>';
        }

        $userForm .= '
        <div class="row mb-6">
            <label class="col-sm-4 col-form-label" for="first_name">First Name</label>
            <div class="col-sm-8">
                <div class="input-group input-group-merge">
                    <span id="firstname-icon" class="input-group-text"><i class="bx bx-first-page"></i></span>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter your First Name" aria-label="First Name" aria-describedby="firstname-icon" value="' . $first_name . '" ' . $required . '>
                </div>
            </div>
        </div>
        <div class="row mb-6">
            <label class="col-sm-4 col-form-label" for="last_name">Last Name</label>
            <div class="col-sm-8">
                <div class="input-group input-group-merge">
                    <span id="lastname-icon" class="input-group-text"><i class="bx bx-last-page"></i></span>
                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter your Last Name" aria-label="Last Name" aria-describedby="lastname-icon" value="' . $last_name . '" ' . $required . '>
                </div>
            </div>
        </div>
        <div class="row mb-6">
            <label class="col-sm-4 col-form-label" for="phone">Phone Number</label>
            <div class="col-sm-8">
                <div class="input-group input-group-merge">
                    <span id="phone-icon" class="input-group-text"><i class="bx bx-phone"></i></span>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter your Phone Number" aria-label="Phone" aria-describedby="phone-icon" value="' . $phone . '" ' . $required . '>
                </div>
            </div>
        </div>
        <div class="row mb-6">
            <label class="col-sm-4 col-form-label" for="email">Email Address</label>
            <div class="col-sm-8">
                <div class="input-group input-group-merge">
                    <span id="email-icon" class="input-group-text"><i class="bx bx-envelope"></i></span>
                    <input type="text" class="form-control" id="email" name="email" placeholder="Enter your Email" aria-label="Email" aria-describedby="email-icon" value="' . $email . '" ' . $required . '>
                </div>
            </div>
        </div>
        ';

        if (Auth::user()->isAdmin()) {
            $req  = ($user_role == 1) ? '' : 'required';
            $sel1 = $this->optionSelected($status, 1);
            $sel2 = $this->optionSelected($status, 2);

            $roleOptions = '';
            foreach ($roles as $role) {
                $selected = in_array($role->id, $user_role) ? 'selected' : ''; // Memeriksa apakah role ada di dalam array
                $roleOptions .= '<option value="' . $role->id . '" ' . $selected . '>' . $role->name . '</option>';
            }

            $userForm .= '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="status">Status</label>
                <div class="col-sm-8">
                    <div class="position-relative">
                        <select class="form-control select2" id="status" name="status" required>
                            <option value=""></option>
                            <option value="1" ' . $sel1 . '>active</option>
                            <option value="2" ' . $sel2 . '>inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="user_role">User Role</label>
                <div class="col-sm-8">
                    <div class="position-relative">
                        <select class="form-control select2" id="user_role" name="user_role[]" multiple required>
                            <option value=""></option>
                            ' . $roleOptions . '
                        </select>
                    </div>
                </div>
            </div>';
        }

        return '
            <div class="card border-0 shadow-none">
                <div class="card-body">
                    ' . $userForm . '
                </div>
            </div>';
    }

    public function edit_pass_form($id)
    {
        $baseForm = '
        <div class="row mb-6">
            <label class="col-sm-4 col-form-label" for="new_password">New Password</label>
            <div class="col-sm-8">
                <div class="input-group input-group-merge">
                    <span id="new-password-icon" class="input-group-text"><i class="bx bx-key"></i></span>
                    <input type="text" class="form-control" id="new_password" name="new_password" placeholder="Enter your New Password" aria-label="New Password" aria-describedby="new-password-icon" required>
                    <span id="generate-password" class="input-group-text bg-primary" role="button"><i class="bx bx-refresh"></i></span>
                </div>
            </div>
        </div>';
        if (Auth::user()->isAdmin() && User::role($id) == 1) {
            $passForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="old_password">Old Password</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="old-password-icon" class="input-group-text"><i class="bx bx-key"></i></span>
                        <input type="text" class="form-control" id="old_password" name="old_password" placeholder="Enter your Old Password" aria-label="Old Password" aria-describedby="old-password-icon" required>
                    </div>
                </div>
            </div>
            ' . $baseForm;
        } elseif (Auth::user()->isAdmin() && user::role($id) != 1) {
            $passForm = $baseForm;
        } else {
            $passForm = '
            <div class="row mb-6">
                <label class="col-sm-4 col-form-label" for="old_password">Old Password</label>
                <div class="col-sm-8">
                    <div class="input-group input-group-merge">
                        <span id="old-password-icon" class="input-group-text"><i class="bx bx-key"></i></span>
                        <input type="text" class="form-control" id="old_password" name="old_password" placeholder="Enter your Old Password" aria-label="Old Password" aria-describedby="old-password-icon" required>
                    </div>
                </div>
            </div>
            ' . $baseForm;
        }

        return '
            <div class="card border-0 shadow-none">
                <div class="card-body">
                    '.$passForm.'
                </div>
            </div>';
    }

    public function edit_image_form()
    {
        $imageForm = '
            <div class="nettix-code my-2"></div>
        ';

        return $imageForm;
    }
    //USER
}
