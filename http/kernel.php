<?php

namespace Nettixcode\Framework\Http;

use Nettixcode\Framework\Routes\Route;

class Kernel
{
    protected $middleware = [];
    protected $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->mergeDefaultMiddleware();
    }

    public function mergeDefaultMiddleware()
    {
        // Ambil middleware dari aplikasi
        $this->middleware = array_merge($this->app->getMiddleware(), $this->middleware);

        // Ambil middleware grup dari aplikasi
        $middlewareGroups = $this->app->getMiddlewareGroups();
        foreach ($middlewareGroups as $group => $middlewares) {
            if (isset($this->middlewareGroups[$group])) {
                $this->middlewareGroups[$group] = array_merge($middlewares, $this->middlewareGroups[$group]);
            } else {
                $this->middlewareGroups[$group] = $middlewares;
            }
        }
    }

    public function handle($request)
    {
        $uri = $request->getUri();
        $middleware = $this->middleware;

        if ($this->isApiRoute($uri)) {
            $middleware = array_merge($middleware, $this->middlewareGroups['api']);
        } else {
            $middleware = array_merge($middleware, $this->middlewareGroups['web']);
        }

        $response = $this->dispatchMiddleware($middleware, $request);

        if ($response !== $request) {
            return $response;
        }

        // Start output buffering
        ob_start();

        // Dispatch route
        Route::dispatch($request);

        // Capture the output at the end of the request
        $output = ob_get_clean();

        // Process middleware that modifies the output after it's captured
        $output = $this->dispatchPostRenderMiddleware($output, $middleware, $request);

        // Display the captured output
        echo $output;
    }

    private function isApiRoute($uri)
    {
        // Parse URL and get path
        $path = parse_url($uri, PHP_URL_PATH);
        $isApi = strpos($path, '/api') === 0;

        return $isApi;
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

    private function dispatchPostRenderMiddleware($output, array $middleware, $request)
    {
        foreach ($middleware as $middlewareClass) {
            if (is_string($middlewareClass) && class_exists($middlewareClass)) {
                $instance = new $middlewareClass();
                if (method_exists($instance, 'modifyOutput')) {
                    $output = $instance->modifyOutput($output, $request);
                }
            } else {
                throw new \Exception("Middleware class $middlewareClass is not valid.");
            }
        }

        return $output;
    }
}
