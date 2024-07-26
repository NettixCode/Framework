<?php

return [
    'default' => [
        'SessionManager' => Nettixcode\Framework\Libraries\SessionManager::class,
        'NxEngine' => Nettixcode\Framework\Libraries\ViewManager::class,
        'User' => Nettixcode\Framework\Libraries\UserManager::class,
        'Config' => Nettixcode\Framework\Libraries\ConfigManager::class,
        'Route' => Nettixcode\Framework\Routes\Route::class,
        'filesystem' => Illuminate\Support\Facades\File::class,
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
