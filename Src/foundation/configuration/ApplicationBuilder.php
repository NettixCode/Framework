<?php

namespace Nettixcode\Framework\Foundation\Configuration;

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\Configuration\Exceptions;

class ApplicationBuilder
{
    protected Application $app;
    protected $routes = [];
    protected $middleware;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->middleware = new Middleware();
        $this->registerProviders();
    }

    public function withRouting(string $web = null, string $api = null, string $commands = null, string $health = null)
    {
        $this->routes = compact('web', 'api', 'commands', 'health');
        return $this;
    }

    public function withMiddleware(?callable $callback = null)
    {
        $this->app->afterResolving(\Nettixcode\Framework\Http\Kernel::class, function ($kernel) use ($callback) {
            $middleware = (new Middleware)
                ->redirectGuestsTo(fn () => route('signin'));

                $callback = $callback ?: function () {};
                $callback($this->middleware);
        });
        return $this;
    }

    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Nettixcode\Framework\Exceptions\Handler::class
        );

        $using ??= fn () => true;

        $this->app->afterResolving(
            \Nettixcode\Framework\Exceptions\Handler::class,
            fn ($handler) => $using(new Exceptions($handler)),
        );

        $this->app->exceptions = new \Nettixcode\Framework\Exceptions\Handler();

        return $this;

    }

    public function create()
    {
        global $debugbar;
        $this->loadRoutes();

        $router = $this->app->getRouter();
        foreach ($this->middleware->getMiddleware() as $middlewares) {
            $router->aliasMiddleware('global', $middlewares);
            $router->middleware('global');
        }

        foreach ($this->middleware->getMiddlewareGroups() as $group => $middlewareGroups) {
            $router->middlewareGroup($group, $middlewareGroups);
        }
        if (isset($debugbar) && isset($debugbar['time'])){
            $debugbar['time']->stopMeasure('Booting');
            $debugbar['time']->startMeasure('Application', 'Application','time');
        }
        return $this->app;
    }

    protected function registerProviders()
    {
        $service = [
            \Nettixcode\Framework\Foundation\Providers\ViewServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Hashing\HashServiceProvider::class,
            \Illuminate\Events\EventServiceProvider::class,
            \Illuminate\Log\LogServiceProvider::class,
        ];
    
        $providers = $this->app['config']->get('app.providers');    
        $mergedProviders = array_merge($service, $providers);
    
        foreach ($mergedProviders as $provider) {
            $this->app->register(new $provider($this->app));
        }
    }
    
    public function addCommand($command)
    {
        $this->app->consoleApplication->add($command);
    }

    protected function loadRoutes()
    {
        foreach ($this->routes as $key => $routeFile) {
            if (file_exists($routeFile)) {
                if ($key === 'commands') {
                    $app = $this->app;
                    require $routeFile;
                } else {
                    $cacheFilePath = $this->app->getCachedRoutesPath();
                    
                    if (file_exists($cacheFilePath)) {
                        require $cacheFilePath;
                    } else {
                        $this->app['router']->group(['middleware' => $key], function () use ($routeFile) {
                            require $routeFile;
                        });
                    }
                }
            } else {
                throw new \Exception("Route file not found: " . $routeFile);
            }
        }
    }
}
