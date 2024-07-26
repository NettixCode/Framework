<?php

namespace Nettixcode\Framework\Libraries\Sources\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\ViewManager;

class NxEngine extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ViewManager::class;
    }
}
