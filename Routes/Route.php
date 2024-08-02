<?php

namespace Nettixcode\Framework\Routes;

use Nettixcode\Framework\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Exceptions\Handler as ExceptionHandler;

class Route
{
    private static $router;
    private static $initialized = false;
    private static $exceptionHandler;

    public static function init()
    {
        if (!self::$initialized) {
            $events = new Dispatcher(new Container);
            self::$router = new Router($events);
            self::$initialized = true;
        }
        // Initialize exception handler
        self::$exceptionHandler = new ExceptionHandler();
    }

    public static function get($path, $callback, $middleware = [])
    {
        return self::addRoute('get', $path, $callback, $middleware);
    }

    public static function post($path, $callback, $middleware = [])
    {
        return self::addRoute('post', $path, $callback, $middleware);
    }

    private static function addRoute($method, $path, $callback, $middleware = [])
    {
        self::init();
        $route = self::$router->$method($path, $callback);
        if (!empty($middleware)) {
            $route->middleware($middleware);
        }
        self::saveRegisteredRoutes(); // Save routes after adding
        return $route; // Return the route instance to allow method chaining
    }

    public static function getRoutes()
    {
        self::init();
        return self::$router->getRoutes();
    }

    public static function dispatch(Request $request)
    {
        self::init();
        $illuminateRequest = $request->getIlluminateRequest();
        $response = self::$router->dispatch($illuminateRequest);

        if ($response->getStatusCode() == 404) {
            self::$exceptionHandler->handleNotFound("No route found for " . $illuminateRequest->path());
        } elseif ($response->getStatusCode() == 405) {
            self::$exceptionHandler->handleForbidden("Method not allowed for " . $illuminateRequest->path());
        }

        return $response;
    }

    public static function saveRegisteredRoutes()
    {
        $filePath = Config::get('app.paths.storage_path') . '/registered_routes.json';
        $routes = [];
        foreach (self::$router->getRoutes() as $route) {
            // Only save GET routes that are not Closures
            if (in_array('GET', $route->methods()) && !($route->getAction('uses') instanceof \Closure)) {
                $name = $route->getName() ?: self::generateRouteName($route->getAction('uses'));
                $routes[$name] = '/' . trim($route->uri(), '/');
            }
        }
        file_put_contents($filePath, json_encode($routes, JSON_PRETTY_PRINT));
    }

    public static function getRegisteredRoutes()
    {
        $filePath = Config::get('app.paths.storage_path') . '/registered_routes.json';
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }
        return [];
    }

    private static function generateRouteName($action)
    {
        if (is_array($action)) {
            return end($action); // Use the method name as the default route name
        } elseif (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            return $method;
        }
        return null;
    }
}
