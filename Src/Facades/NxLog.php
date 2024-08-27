<?php

namespace Nettixcode\Framework\Facades;

use Illuminate\Support\Facades\Facade;

class NxLog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}
