<?php

namespace Nettixcode\Framework\Libraries\Sources\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\ConfigManager;

class Config extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ConfigManager::class;
    }
}
