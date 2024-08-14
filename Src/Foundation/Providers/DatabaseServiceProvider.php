<?php

namespace Nettixcode\Framework\Foundation\Providers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $capsule = new Capsule();
        $capsule->addConnection($this->app['config']->get('database.connections')[$this->app['config']->get('database.default')]);
        $capsule->setEventDispatcher(new Dispatcher($this->app));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->app->instance('capsule', $capsule);
        $this->app->instance('db', $capsule);

        $this->app->singleton('db', function ($app) {
            return $app['capsule'];
        });

        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->schema();
        });

        Schema::setFacadeApplication($this->app);
    }

    public function boot()
    {
        // Nothing to boot
    }

}
