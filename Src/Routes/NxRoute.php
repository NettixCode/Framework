<?php

Route::middleware(['web'])->group(function () {
    Route::middleware(['auth','checkIncomplete'])->group(function () {
        Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health');
        Route::prefix('admin')->group(function () {
            Route::prefix('settings')->group(function(){
                Route::get('/app-settings', [AppSettingsController::class, 'index'])->name('app-settings');
                Route::get('/provider-settings', [ProviderSettingsController::class, 'index'])->name('provider-settings');
                Route::get('/debugbar-settings', [DebugbarSettingsController::class, 'index'])->name('debugbar-settings');
            });
            Route::prefix('roles-permissions')->group(function(){
                Route::get('/roles', [RoleController::class, 'index'])->name('roles');
                Route::get('/permission', [PermissionController::class, 'index'])->name('permission');
            });
            Route::get('/users', [UsersController::class, 'index'])->name('users');
        });

        Route::prefix('api')->group(function () {
            Route::prefix('json')->group(function () {
                Route::post('/roles/table-role', [RoleController::class, 'table_role']);
                Route::post('/permission/table-permission', [PermissionController::class, 'table_permission']);
                Route::post('/users', [UsersController::class, 'table']);
            });
            Route::prefix('submit')->group(function () {
                Route::post('/roles/create', [RoleController::class, 'create']);
                Route::post('/roles/edit', [RoleController::class, 'update']);
                Route::post('/roles/delete', [RoleController::class, 'delete']);
                Route::post('/permission/create', [PermissionController::class, 'create']);
                Route::post('/permission/edit', [PermissionController::class, 'update']);
                Route::post('/permission/delete', [PermissionController::class, 'delete']);
                Route::post('/app-settings/edit', [AppSettingsController::class, 'update']);
                Route::post('/provider-settings/edit', [ProviderSettingsController::class, 'update']);
                Route::post('/debugbar-settings/edit', [DebugbarSettingsController::class, 'update']);

                Route::post('/users/create', [UsersController::class, 'create']);
                Route::post('/users/edit', [UsersController::class, 'update']);
                Route::post('/users/edit_pass', [UsersController::class, 'update_pass']);
                Route::post('/users/edit_image', [UsersController::class, 'update_image']);
                Route::post('/users/delete', [UsersController::class, 'delete']);
            });
        });
    });
});
