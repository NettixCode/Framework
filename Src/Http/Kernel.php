<?php

namespace Nettixcode\Framework\Http;

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Facades\Route;
use Nettixcode\Framework\Facades\Config;
use Illuminate\Http\JsonResponse;

class Kernel
{
    protected $app;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->RouteGenerate();
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

    public function handle($request)
    {
        try {
            if ($this->isForbidden($request->getPathInfo())) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Forbidden');
            }
    
            $router = app('router');
            ob_start();
            Route::dispatch($request);
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
        } catch (\Exception $e) {
            $handler = app()->exceptions;

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                $handler->handleNotFound($e);
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                $handler->handleForbidden($e);
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() >= 500) {
                $handler->handleServerError($e);
            }
        }
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
        
        return $output;
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
