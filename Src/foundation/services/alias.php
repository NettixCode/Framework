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
            'filesystem' => \Illuminate\Support\Facades\File::class,
            'app' => [get_class($this->app), \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'blade.compiler' => [\Illuminate\View\Compilers\BladeCompiler::class],
            'events' => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files' => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem' => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
            'log' => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
        ];

        $controllerAliases = [
            'RolePermissionController' => \Nettixcode\Framework\Controllers\RolePermissionController::class,
            'PageBuilderController' => \Nettixcode\Framework\Controllers\PageBuilderController::class,
        ];

        $customAliases = $this->app['config']->get('aliases.controller', []);

        $allAliases = array_merge($aliases, $controllerAliases, $customAliases);

        AliasLoader::getInstance($allAliases)->register();
    }
}
