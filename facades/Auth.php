<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;
use Nettixcode\Framework\Libraries\UserManager;

class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
