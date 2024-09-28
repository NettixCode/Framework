<?php

namespace Nettixcode\App\Console;

use Illuminate\Support\ServiceProvider;
use Nettixcode\App\Console\TablePermissionCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            "TablePermission" => TablePermissionCommand::class,
        ]);
    }
}
