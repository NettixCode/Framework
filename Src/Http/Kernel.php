<?php

namespace Nettixcode\Framework\Http;

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Facades\Route;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Facades\NxLog;
use Illuminate\Http\JsonResponse;
use Whoops\Run as WhoopsRun;
use Whoops\Handler\PrettyPageHandler;

class Kernel
{
    protected $app;
    protected $middleware = [];
    protected $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    public function __construct()
    {
        $this->app = Application::getInstance();
        // $this->RouteGenerate();
        $this->registerRouteToMiddleware();
    }

    private function RouteGenerate()
    {
        $cacheFilePath = $this->app->getCachedRoutesPath();
        if (!file_exists($cacheFilePath)) {
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
    }

    protected function registerRouteToMiddleware()
    {
        $router = $this->app->getRouter();

        foreach ($this->middleware as $middlewareClass) {
            $router->aliasMiddleware('global', $middlewareClass);
            $router->middleware('global');
        }

        foreach ($this->middlewareGroups as $group => $middlewareClasses) {
            $router->middlewareGroup($group, $middlewareClasses);
        }
    }

    public function setMiddleware(array $middleware)
    {
        $this->middleware = $middleware;
    }

    public function setMiddlewareGroups(array $middlewareGroups)
    {
        $this->middlewareGroups = $middlewareGroups;
    }

    public function handle($request)
    {
        try {
            if ($this->isForbidden($request->getPathInfo())) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Forbidden');
            }
    
            $uri = $request->getUri();
            $middleware = $this->middleware;
        
            $route = app('router')->getRoutes()->match($request);
            $middlewares = $route->gatherMiddleware();

            if (in_array('api', $middlewares)) {
                $middleware = array_merge($middleware, $this->middlewareGroups['api']);
            } else {
                $middleware = array_merge($middleware, $this->middlewareGroups['web']);
            }
            
            $response = $this->dispatchMiddleware($middleware, $request);
        
            if ($response instanceof JsonResponse) {
                return $response;
            }
        
            $router = app('router');
            ob_start();
            $router->dispatch($response);
            $output = ob_get_clean();
    
            if ($this->isJson($output)) {
                return new JsonResponse(json_decode($output, true));
            }
    
            $output = $this->addCsrfToken($output);
    
            try {
                if (isset($this->app->debugger) && $this->app->debugger->hasStarted('application')) {
                    $this->app->debugger->stopMeasure('application');
                }
            } catch (\Exception $e) {
                error_log('Error stopping application measure: ' . $e->getMessage());
            }
    
            echo $output;
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function dispatchMiddleware(array $middleware, $request)
    {
        $index = 0;

        $next = function ($request) use (&$index, $middleware, &$next) {
            if ($index < count($middleware)) {
                $middlewareClass = $middleware[$index++];
                if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                    $instance = new $middlewareClass();
                    return $instance->handle($request, $next);
                } else {
                    throw new \Exception("Middleware class $middlewareClass is not valid.");
                }
            }

            return $request;
        };

        return $next($request);
    }

    protected function handleException(\Throwable $e)
    {
        $handler = app()->exceptions;
        $appDebug = env('APP_DEBUG', false);
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $handler->handleNotFound($e);
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            $handler->handleForbidden($e);
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() >= 500) {
            $handler->handleServerError($e);
        } elseif ($appDebug) {
            $this->handleWithWhoops($e);
        } else {
            $handler->handleServerError($e);
        }
    }
        
    private function handleWithWhoops(\Throwable $e)
    {
        $whoops = new WhoopsRun;
        $whoops->pushHandler(new PrettyPageHandler);
        $whoops->handleException($e);
    }

    private static function isForbidden($uri)
    {
        $forbiddenUris = ['/uploads/', '/img/', '/js/', '/css/', '/plugins/', '/scss/'];

        return in_array($uri, $forbiddenUris);
    }

    private function generateRouteName($action)
    {
        if (is_array($action)) {
            return end($action);
        } elseif (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            return $method;
        }    
        return null;
    }    

    private function addCsrfToken($output)
    {
        $token = csrf_token();
        $hiddenInput = '<input type="hidden" name="_token" value="' . $token . '">';
        $output = preg_replace('/(<form\b[^>]*>)/i', '$1' . $hiddenInput, $output);
        
        NxLog::info('CSRF Token Generated');
        
        return $output;
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
