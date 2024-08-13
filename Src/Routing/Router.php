<?php

namespace Nettixcode\Framework\Routing;

use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Events\Dispatcher;
use Nettixcode\Framework\Facades\App;

class Router extends IlluminateRouter
{
    public function __construct()
    {
        $events = new Dispatcher(App::getInstance());
        parent::__construct($events);
    }
}
