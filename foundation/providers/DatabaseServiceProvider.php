<?php

namespace Nettixcode\Framework\Foundation\Providers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Nettixcode\Framework\Facades\Config;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('db', function ($app) {
            return $app['capsule'];
        });

        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->schema();
        });

        $this->registerDatabase();

        \Illuminate\Support\Facades\Schema::setFacadeApplication($this->app);
    }

    public function boot()
    {
        // Nothing to boot
    }

    protected function registerDatabase()
    {
        $capsule = new Capsule();
        $capsule->addConnection(Config::get('database.connections')[Config::get('database.default')]);
        $capsule->setEventDispatcher(new Dispatcher($this->app));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->app->instance('capsule', $capsule);
        $this->app->instance('db', $capsule);
    }
}
