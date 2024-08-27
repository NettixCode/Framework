<?php

namespace Nettixcode\Framework\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;
use Closure;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The route callback that will be used to load routes.
     *
     * @var \Closure|null
     */
    protected static $routeCallback;

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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutes();
        $this->generateRouteNames();
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

}
