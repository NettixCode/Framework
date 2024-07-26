<?php

namespace Nettixcode\Framework\Providers;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use Nettixcode\Framework\Libraries\ConfigManager as Config;
use Nettixcode\Framework\Core\Route;
use Nettixcode\Framework\Libraries\AliasManager;
use Nettixcode\Framework\Libraries\UserManager;
use Nettixcode\Framework\Libraries\SessionManager;

class FrameworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        
        $this->app->singleton('config', function () {
            return new Config();
        });

        $basePath = $this->app['config']::load('app', 'paths.base_path');
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->load();

        $this->registerDatabase();

        $this->app->singleton('route', function () {
            return new Route();
        });

        $this->app->singleton('usermanager', function($app) {
            return new UserManager();
        });

        $this->app->singleton('files', function () {
            return new Filesystem();
        });

        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
    }

    public function boot()
    {
        date_default_timezone_set($this->app['config']::load('app', 'timezone'));
        $this->generateAliases();
        $this->loadRoutes();
        SessionManager::getInstance();
    }

    protected function generateAliases()
    {
        AliasManager::generate();
        $aliases = require $this->app['config']::load('framework', 'files.aliases');
        foreach ($aliases as $group => $groupAliases) {
            foreach ($groupAliases as $alias => $class) {
                class_alias($class, $alias);
            }
        }
    }

    protected function registerDatabase()
    {
        $capsule = new Capsule();
        $capsule->addConnection($this->app['config']::load('database', 'connections')[$this->app['config']::load('database', 'default')]);
        $capsule->setEventDispatcher(new Dispatcher($this->app));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->app->instance('capsule', $capsule);
        $this->app->instance('db', $capsule);
    }

    protected function loadRoutes()
    {
        require $this->app['config']::load('app', 'routes.web');
        require $this->app['config']::load('app', 'routes.api');
        require $this->app['config']::load('framework', 'files.helper');
    }
}
