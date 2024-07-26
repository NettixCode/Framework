<?php

return [
    'default' => [
        'SessionManager' => Nettixcode\Framework\Libraries\SessionManager::class,
        'Config' => Nettixcode\Framework\Libraries\Sources\Facades\Config::class,
        'User' => Nettixcode\Framework\Libraries\Sources\Facades\User::class,
        'Route' => Nettixcode\Framework\Core\Route::class,
        'filesystem' => Illuminate\Support\Facades\File::class,
        'Role' => Nettixcode\Framework\Libraries\Sources\Models\Role::class,
        'Permission' => Nettixcode\Framework\Libraries\Sources\Models\Permission::class,
    ],
    'default_controller' => [
        'RolePermissionController' => Nettixcode\Framework\Libraries\Sources\Controllers\RolePermissionController::class,
        'PageBuilderController' => Nettixcode\Framework\Libraries\Sources\Controllers\PageBuilderController::class,
    ],
    'controller' => [
        'ModalController' => Application\Http\Controllers\ModalController::class,
        'PageController' => Application\Http\Controllers\PageController::class,
        'UsersController' => Application\Http\Controllers\UsersController::class,
    ],
];
