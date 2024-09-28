<?php

namespace Nettixcode\Routes;

use Illuminate\Support\ServiceProvider;

class NxRouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(nettixcode_path('Routes/NxRoute.php'));
    }
}
