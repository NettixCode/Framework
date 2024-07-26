<?php

namespace Nettixcode\Framework\Core;

use Nettixcode\Framework\Http\Request;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Libraries\Sources\Facades\NxEngine;

class Route
{
    private static $dispatcher;

    private static $routes = [];

    private static $registeredRoutes = [];

    private static $initialized = false;

    private static $currentRouteName = null;

    public static function init()
    {
        if (!self::$initialized) {
            self::$dispatcher = simpleDispatcher(function (RouteCollector $r) {
                foreach (self::$routes as $route) {
                    $r->addRoute($route['method'], $route['path'], [
                        'callback'   => $route['callback'],
                        'middleware' => $route['middleware'],
                    ]);
                }
            });
            self::$initialized = true;
            self::saveRegisteredRoutes();
        }
    }

    public static function get($path, $callback, $middleware = [])
    {
        self::addRoute('GET', $path, $callback, $middleware);

        return new class () {
            public function name($routeName)
            {
                Route::setCurrentRouteName($routeName);
                Route::registerCurrentRouteName();

                return $this;
            }
        };
    }

    public static function post($path, $callback, $middleware = [])
    {
        self::addRoute('POST', $path, $callback, $middleware);

        return new class () {
            public function name($routeName)
            {
                Route::setCurrentRouteName($routeName);
                Route::registerCurrentRouteName();

                return $this;
            }
        };
    }

    private static function addRoute($method, $path, $callback, $middleware = [])
    {
        foreach (self::$routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return; // Jika rute sudah ada, lewati penambahan
            }
        }

        self::$routes[] = [
            'method'     => $method,
            'path'       => $path,
            'callback'   => $callback,
            'middleware' => (array) $middleware,
        ];

        if (strtoupper($method) === 'GET' && !self::$currentRouteName) {
            $routeName = is_array($callback) ? strtolower($callback[1]) : $callback;
            self::registerRouteName($routeName, $path);
        }
    }

    public static function setCurrentRouteName($routeName)
    {
        self::$currentRouteName = $routeName;
    }

    public static function registerCurrentRouteName()
    {
        if (self::$currentRouteName) {
            $routeName              = self::$currentRouteName;
            self::$currentRouteName = null;

            $path = end(self::$routes)['path'];
            self::registerRouteName($routeName, $path);
        }
    }

    private static function registerRouteName($routeName, $path)
    {
        if (!isset(self::$registeredRoutes[$routeName])) {
            self::$registeredRoutes[$routeName] = $path;
        }
    }

    public static function getRegisteredRoutes()
    {
        return self::$registeredRoutes;
    }

    public static function dispatch($request)
    {
        self::init(); // Pastikan dispatcher diinisialisasi
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        if (self::isForbidden($uri)) {
            self::handleForbidden("Access forbidden for $uri");

            return;
        }

        $routeInfo = self::$dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                self::handleNotFound("No route found for $uri with method $method");
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                self::handleForbidden("Method not allowed for $uri");
                break;
            case \FastRoute\Dispatcher::FOUND:
                $route       = $routeInfo[1];
                $vars        = $routeInfo[2];
                $callback    = $route['callback'];
                $middlewares = $route['middleware'];

                if (!empty($middlewares)) {
                    $next = function ($request) use ($callback, $vars) {
                        if (is_callable($callback)) {
                            call_user_func($callback, $vars);
                        } elseif (is_array($callback)) {
                            Route::callAction($callback, $vars);
                        }
                    };

                    foreach (array_reverse($middlewares) as $middleware) {
                        $next = function ($request) use ($middleware, $next) {
                            $middlewareInstance = new $middleware();

                            return $middlewareInstance->handle($request, $next);
                        };
                    }

                    $next($request); // Pass the request object to middleware chain
                } else {
                    if (is_callable($callback)) {
                        call_user_func($callback, $vars);
                    } elseif (is_array($callback)) {
                        self::callAction($callback, $vars);
                    }
                }
                break;
        }
    }

    private static function callAction($action, $vars)
    {
        if (is_array($action)) {
            list($controller, $method) = $action;
            $controller                = new $controller();
            $request                   = new Request();
            call_user_func([$controller, $method], $request, $vars);
        } else {
            list($controller, $method) = explode('@', $action);
            $controller                = new $controller();
            $request                   = new Request();
            call_user_func([$controller, $method], $request, $vars);
        }
    }

    private static function handleNotFound($message = '404 Not Found')
    {
        http_response_code(404);
        NxEngine::redirectToErrorPage(404);
        error_log($message);
    }

    public static function handleForbidden($message = '403 Forbidden')
    {
        http_response_code(403);
        NxEngine::redirectToErrorPage(403);
        error_log($message);
    }

    public static function handleServerError($message = '500 Internal Server Error')
    {
        http_response_code(500);
        NxEngine::redirectToErrorPage(500);
        error_log($message);
    }

    private static function isForbidden($uri)
    {
        $forbiddenUris = ['/uploads/', '/img/', '/js/', '/css/', '/plugins/', '/scss/'];

        return in_array($uri, $forbiddenUris);
    }

    private static function saveRegisteredRoutes()
    {
        $filePath = Config::load('app','paths.storage_path').'/registered_routes.json';
        file_put_contents($filePath, json_encode(self::$registeredRoutes, JSON_PRETTY_PRINT));
    }
}
