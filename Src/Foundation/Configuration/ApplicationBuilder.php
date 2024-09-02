<?php

namespace Nettixcode\Framework\Foundation\Configuration;

use Closure;
use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\Configuration\Exceptions;
use Nettixcode\Framework\Foundation\Configuration\Middleware;
use Nettixcode\Framework\Facades\NxLog;
Use Nettixcode\Framework\Facades\Config;

class ApplicationBuilder
{
    protected Application $app;
    // protected $routes = [];
    protected $routesCallback;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function withKernels()
    {
        $this->app->singleton(
            \Nettixcode\Framework\Http\Kernel::class,
            function ($app) {
                return new \Nettixcode\Framework\Http\Kernel($app);
            }
        );
    
        $this->app->singleton(
            \Nettixcode\Framework\Console\Kernel::class,
            function ($app) {
                return new \Nettixcode\Framework\Console\Kernel($app);
            }
        );

        return $this;
    }

    public function withRouting(?Closure $using = null,
    array|string|null $web = null,
    array|string|null $api = null,
    ?string $commands = null,
    ?string $health = null,
    ?string $apiPrefix = null)
    {
        if (is_null($using) && (is_string($web) || is_array($web) || is_string($api) || is_array($api) || is_string($pages) || is_string($health)) || is_callable($then)) {
            $using = $this->buildRoutingCallback($web, $api, $health, $apiPrefix);
        }
        // $this->routes = compact('web', 'api');
        $prefix = !is_null($apiPrefix) ? $apiPrefix : '';
        setcookie('apiPrefix', $prefix, 0, '/', '', true, false);
        if ($using) {
            \Nettixcode\Framework\Foundation\Providers\RouteServiceProvider::loadRoutesUsing($using);

            $this->app->booting(function () {
                $this->app->register(\Nettixcode\Framework\Foundation\Providers\RouteServiceProvider::class, true);
            });
        }

        if ($commands && file_exists($commands)) {
            $this->withCommands($commands);
        }

        return $this;
    }
    
    protected function buildRoutingCallback(array|string|null $web,
    array|string|null $api,
    ?string $health,
    ?string $apiPrefix)
    {
        return function () use ($web, $api, $health, $apiPrefix) {
            if ($web) {
                if (is_array($web)) {
                    foreach ($web as $webRoute) {
                        if (file_exists($webRoute)) {
                            $this->app['router']->middleware('web')->group($webRoute);
                        }
                    }
                } elseif (is_string($web) && file_exists($web)) {
                    $this->app['router']->middleware('web')->group($web);
                }
            }
    
            if ($api) {
                if (is_array($api)) {
                    foreach ($api as $apiRoute) {
                        if (file_exists($apiRoute)) {
                            $this->app['router']->middleware('api')->prefix($apiPrefix)->group($apiRoute);
                        }
                    }
                } elseif (is_string($api) && file_exists($api)) {
                    $this->app['router']->middleware('api')->prefix($apiPrefix)->group($api);
                }
            }
            
            if (is_string($health) && !empty($health)) {
                $this->app['router']->get($health, function () {
                    return response()->json(['status' => 'OK'], 200);
                });
            }
        };
    }

    public function withMiddleware(?callable $callback = null)
    {
        $this->app->afterResolving(\Nettixcode\Framework\Http\Kernel::class, function ($kernel) use ($callback) {
            $middleware = (new Middleware);
            if (! is_null($callback)) {
                $callback($middleware);
            }

            $kernel->setMiddleware($middleware->getMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());    
        });
        return $this;
    }

    public function withCommands(?string $command = null)
    {
        $default = require Config::get('framework.files.command');
        if (empty($command) || is_null($command)) {
            $commands = $default;
        } else {
            $command_added = require $command;
            $commands = array_merge($command_added,$default);
        }
        
        $kernel = new \Nettixcode\Framework\Console\Kernel($this->app);
        $kernel->addCommands($commands);
    
        return $this;
    }
        
    public function withProviders(?array $provide = [])
    {
        $service = [
            \Illuminate\Cache\CacheServiceProvider::class,
            \Nettixcode\Framework\Foundation\Providers\ViewServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Hashing\HashServiceProvider::class,
        ];
    
        $providers = $this->app['config']->get('app.providers');
        $mergedProviders = array_merge($service, $providers , $provide);
    
        foreach ($mergedProviders as $provider) {
            $this->app->register(new $provider($this->app));
        }

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
    
        if (isset($debugbar) && isset($debugbar['time'])){
            $debugbar['time']->stopMeasure('Booting');
            $debugbar['time']->startMeasure('Application', 'Application', 'time');
        }
    
        return $this->app;
    }
    
    // public function addCommand($command)
    // {
    //     if (isset($this->app->consoleApplication)){
    //         $this->app->consoleApplication->add($command);
    //     }
    // }

    // protected function loadRoutes()
    // {
    //     foreach ($this->routes as $key => $routeFile) {
    //         if (file_exists($routeFile)) {
    //             $cacheFilePath = $this->app->getCachedRoutesPath();                    
    //             if (file_exists($cacheFilePath)) {
    //                 require $cacheFilePath;
    //             } else {
    //                 require $routeFile;
    //             }
    //         } else {
    //             throw new \Exception("Route file not found: " . $routeFile);
    //         }
    //     }
    // }
}
