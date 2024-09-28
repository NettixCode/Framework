<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

$providerJson = storage_path('app/private/nxcode-provider.json');
$package_provider = [];
$package_provider_disabled = [];
$application_provider = [];
$application_provider_except = [];
$added_provider = [];
$added_provider_disabled = [];

$json = file_get_contents($providerJson);
$config = json_decode($json, true);

foreach ($config['package_providers'] as $prov){
    if (!$prov['enabled']){
        $package_provider_disabled[] =  $prov['provider'];
    } else {
        $package_provider[] = $prov['provider'];
    }
}

foreach ($config['application_providers'] as $prov){
    if (!$prov['enabled']){
        $application_provider_except[] =  $prov['provider'];
    } else {
        $application_provider[] = $prov['provider'];
    }
}

foreach ($config['added_providers'] as $prov){
    if (!$prov['enabled']){
        $added_provider_disabled[] =  $prov['provider'];
    } else {
        $added_provider[] = $prov['provider'];
    }
}


return [
    'name' => env('APP_NAME', 'nettixcode'),

    'env' => env('APP_ENV', 'production'),

    'nxcode_resource' => env('COMPOSER_VENDOR_DIR', base_path('vendor')).'/nettixcode/framework/resources/views',

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'providers' => ServiceProvider::defaultProviders()
    ->merge(array_merge(
        $package_provider,
        []
    ),
    // )->merge(array_merge(
        // $application_provider,
        // []
    // ),
    )->except(array_merge(
        $application_provider_except,
        []
    ),
    )->merge(array_merge(
        $added_provider,
        []
    ),
    )->toArray(),

    'aliases' => Facade::defaultAliases()->merge(
        array_merge([
                'AppSettingsController' => \Nettixcode\App\Http\Controllers\AppSettingsController::class,
                'checkIncomplete' => \Nettixcode\App\Http\Middleware\AuthIncompleteProfile::class,
                'DebugbarSettingsController' => \Nettixcode\App\Http\Controllers\DebugbarSettingsController::class,
                'PermissionController' => \Nettixcode\App\Http\Controllers\PermissionController::class,
                'ProviderSettingsController' => \Nettixcode\App\Http\Controllers\ProviderSettingsController::class,
                'RoleController' => \Nettixcode\App\Http\Controllers\RoleController::class,
                'SystemHealthController' => \Nettixcode\App\Http\Controllers\SystemHealthController::class,
                'User' => \Nettixcode\Facades\User::class,
            ],
            require storage_path('framework/cache/data/controlleralias.php'),
            require config_path('aliases.php')
        )
    )->toArray(),
];
