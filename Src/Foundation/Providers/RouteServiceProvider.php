<?php

namespace Nettixcode\Framework\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use Closure;

class RouteServiceProvider extends ServiceProvider
{
    use ForwardsCalls;
    /**
     * The route callback that will be used to load routes.
     *
     * @var \Closure|null
     */
    protected static $routeCallback;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {    
        $this->app->isBooting(function ($app) {
            if ($this->routesAreCached()) {
                $this->loadCachedRoutes();
            } else {
                $this->loadRoutes();
                $app['router']->getRoutes()->refreshNameLookups();
                $app['router']->getRoutes()->refreshActionLookups();
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    protected function routesAreCached()
    {
        return $this->app->routesAreCached();
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        // if (! is_null(self::$alwaysLoadCachedRoutesUsing)) {
        //     $this->app->call(self::$alwaysLoadCachedRoutesUsing);

        //     return;
        // }

        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Register the callback used to load the routes.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function loadRoutesUsing(Closure $callback)
    {
        static::$routeCallback = $callback;
        // dd($callback);
    }

    /**
     * Load the routes for the application.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (static::$routeCallback) {
            call_user_func(static::$routeCallback);
            // $router = $this->app['router'];
        }
    }

    /**
     * Generate route names for routes without a name.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    private function generateRouteNames()
    {
        $router = app('router');
        $routes = $router->getRoutes()->getRoutes();

        $routeCollection = new \Illuminate\Routing\RouteCollection();

        foreach ($routes as $route) {
            $newRoute = new \Illuminate\Routing\Route(
                $route->methods(),
                $route->uri(),
                $route->getAction()
            );

            if ($route->getName()) {
                $generatedName = $route->getName();
            } else {
                $generatedName = $this->generateRouteName($route->getAction('uses'));
                $newRoute->name($generatedName);
            }

            $newRoute->middleware($route->gatherMiddleware());
            $routeCollection->add($newRoute);
        }

        $urlGenerator = app('url');
        $urlGenerator->setRoutes($routeCollection);
    }

    /**
     * Generate a name for the route based on the action.
     *
     * @param mixed $action
     * @return string|null
     */
    protected function generateRouteName($action)
    {
        if (is_array($action)) {
            return end($action);
        } elseif (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            return $method;
        }

        return null;
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->app->make(Router::class), $method, $parameters
        );
    }
}
