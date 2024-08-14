<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;
use \Nettixcode\Framework\Foundation\Application;

class App extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}
