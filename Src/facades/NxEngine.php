<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Foundation\Manager\ViewManager;

class NxEngine extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'nxengine';
    }
}
