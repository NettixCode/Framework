<?php

namespace Nettixcode\Framework\Http;

use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Core\Route;

class Kernel
{
    protected $middleware = [
        'Nettixcode\Framework\Libraries\Sources\Middleware\AdminRestricted',
        'Nettixcode\Framework\Libraries\Sources\Middleware\GenerateCsrfToken',
        'Nettixcode\Framework\Libraries\Sources\Middleware\RateLimit',
    ];

    protected $middlewareGroups = [
        'web' => [
            'Nettixcode\Framework\Libraries\Sources\Middleware\GenerateJwtToken',
        ],
        'api' => [
            'Nettixcode\Framework\Libraries\Sources\Middleware\VerifyCsrfToken',
            'Nettixcode\Framework\Libraries\Sources\Middleware\VerifyJwtToken',
        ],
    ];

    public function __construct()
    {
        $this->mergeDefaultMiddleware();
    }

    public function mergeDefaultMiddleware()
    {
        // $defaultMiddleware = require __DIR__ . '/../../config/app.php';
        $this->middleware = array_merge(Config::load('middleware', 'middleware.global'), $this->middleware);
        foreach (Config::load('middleware', 'middleware.groups') as $group => $middlewares) {
            if (isset($this->middlewareGroups[$group])) {
                $this->middlewareGroups[$group] = array_merge($middlewares, $this->middlewareGroups[$group]);
            } else {
                $this->middlewareGroups[$group] = $middlewares;
            }
        }
    }

    public function handle($request)
    {
        $uri        = $request->getUri();
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
        $path  = parse_url($uri, PHP_URL_PATH);
        $isApi = strpos($path, '/api') === 0;

        return $isApi;
    }

    private function dispatchMiddleware(array $middleware, $request)
    {
        $index = 0;

        $next = function ($request) use (&$index, $middleware, &$next) {
            if ($index < count($middleware)) {
                $instance = new $middleware[$index++]();

                return $instance->handle($request, $next);
            }

            return $request;
        };

        return $next($request);
    }

    private function dispatchPostRenderMiddleware($output, array $middleware, $request)
    {
        foreach ($middleware as $m) {
            $instance = new $m();
            if (method_exists($instance, 'modifyOutput')) {
                $output = $instance->modifyOutput($output, $request);
            }
        }

        return $output;
    }
}
