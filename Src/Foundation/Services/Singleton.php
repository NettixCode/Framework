<?php

namespace Nettixcode\Framework\Foundation\Services;

use Illuminate\Log\LogManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\Manager\AuthManager;
use Nettixcode\Framework\Foundation\Manager\ConfigManager;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Foundation\Manager\UserManager;
use Nettixcode\Framework\Foundation\Manager\ViewManager;
use Nettixcode\Framework\Http\Request;
use Psr\Log\LoggerInterface;

class Singleton
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->registerSingletons();
    }

    protected function registerSingletons()
    {
        $this->app->singleton('session', function () {
            return SessionManager::getInstance();
        });
        
        $this->app->singleton('files', function () {
            return new Filesystem();
        });
        
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
        
        $this->app->singleton('user', function() {
            return new UserManager();
        });
        
        $this->app->singleton('auth', function() {
            return new AuthManager();
        });
        
        $this->app->singleton('nxengine', function() {
            return new ViewManager();
        });
        
        $this->app->singleton('request', function () {
            return Request::capture();
        });
    
        $this->app->singleton('log', function ($app) {
            return new LogManager($app);
        });

        $this->app->singleton('cachedRoutesPath', function () {
            return $this->app->getCachedRoutesPath();
        });
    }
}
