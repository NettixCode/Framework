<?php

namespace Nettixcode\View;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->app->getProvider(\Illuminate\View\ViewServiceProvider::class)) {
            $this->app->register(\Illuminate\View\ViewServiceProvider::class);
        }
    }
    /**
     * Bootstrap view services.
     *
     * @return void
     */
    public function boot(): void
    {
        View::addLocation(app('config')->get('app.nxcode_resource'));
        View::addExtension(app('config')->get('view.blade_extension','blade').'.php', 'blade');
    }
}
