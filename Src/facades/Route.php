<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Foundation\Manager\UserManager;

class Route extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
