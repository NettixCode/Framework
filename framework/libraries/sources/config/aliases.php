<?php

return [
    'default' => [
        'SessionManager' => Nettixcode\Framework\Libraries\SessionManager::class,
        'User' => Nettixcode\Framework\Libraries\Sources\Facades\User::class,
        'Config' => Nettixcode\Framework\Libraries\Sources\Facades\Config::class,
        'Route' => Nettixcode\Framework\Core\Route::class,
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
