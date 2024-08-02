<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\ConfigManager;

class Config extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
