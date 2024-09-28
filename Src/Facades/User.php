<?php

namespace Nettixcode\Facades;

use Nettixcode\App\Models\User as UserManager;
use Illuminate\Support\Facades\Facade;

class User extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UserManager::class;
    }
}
