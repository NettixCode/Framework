<?php

namespace Nettixcode\Manager;

use Illuminate\Support\ServiceProvider;
use Nettixcode\Manager\ControllerAliasManager;

class ControllerAliasManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        ControllerAliasManager::generate();
    }
}
