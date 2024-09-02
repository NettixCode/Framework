<?php

namespace Nettixcode\Framework\Foundation\Services;

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\AliasLoader;
use Nettixcode\Framework\Foundation\Manager\AliasManager;

class Alias
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->generateAliases();

        $this->loadAliases();
    }

    protected function generateAliases()
    {
        AliasManager::generate();
    }

    protected function loadAliases()
    {
        $aliases = [
            'SessionManager' => \Nettixcode\Framework\Foundation\Manager\SessionManager::class,
            'NxEngine' => \Nettixcode\Framework\Facades\NxEngine::class,
            'Auth' => \Nettixcode\Framework\Facades\Auth::class,
            'User' => \Nettixcode\Framework\Facades\User::class,
            'Config' => \Nettixcode\Framework\Facades\Config::class,
            'Route' => \Illuminate\Support\Facades\Route::class,
            'NxLog' => \Illuminate\Support\Facades\Log::class,
            'filesystem' => \Illuminate\Support\Facades\File::class,
            'app' => [get_class($this->app), \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'blade.compiler' => [\Illuminate\View\Compilers\BladeCompiler::class],
            'events' => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files' => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem' => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
            'log' => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
            'url' => [\Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
        ];

        $controllerAlias = [
            'RoleController' => \Nettixcode\Framework\Controllers\RoleController::class,
            'PermissionController' => \Nettixcode\Framework\Controllers\PermissionController::class,
            'AppSettingsController' => \Nettixcode\Framework\Controllers\AppSettingsController::class,
            'SystemHealthController' => \Nettixcode\Framework\Controllers\SystemHealthController::class,
        ];

        $customAliases = $this->app['config']->get('aliases', []);
        $customControllerAliasesPath = $this->app['config']->get('app.cache_paths.data',[]).'/controlleraliases.php';

        $customControllerAliases = file_exists($customControllerAliasesPath) ? require $customControllerAliasesPath : [];
        
        $controllerAliases = isset($customControllerAliases['controller']) && is_array($customControllerAliases['controller'])
            ? $customControllerAliases['controller']
            : [];
        
        $allAliases = array_merge($aliases, $controllerAlias, $customAliases, $controllerAliases);

        AliasLoader::getInstance($allAliases)->register();
    }
}
