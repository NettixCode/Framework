<?php

namespace Nettixcode\Framework\Libraries\Sources\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\UserManager;

class User extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'user';
    }
}
