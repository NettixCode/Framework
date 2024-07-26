<?php

namespace Nettixcode\Framework\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Binding DB facade
        $this->app->singleton('db', function ($app) {
            return $app['capsule'];
        });

        // Binding Schema facade
        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->schema();
        });

        // Set Facade Application
        \Illuminate\Support\Facades\Schema::setFacadeApplication($this->app);
    }

    public function boot()
    {
        // Nothing to boot
    }
}
