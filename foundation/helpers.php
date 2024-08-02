<?php

use Nettixcode\Framework\Foundation\Application;

if (! function_exists('base_path')) {
    /**
     * Get the base path of the application.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return Application::getInstance()->basePath($path);
    }
}

if (! function_exists('public_path')) {
    function public_path($path = '')
    {
        return base_path('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (! function_exists('config_path')) {
    function config_path($path = '')
    {
        return base_path('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (! function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return base_path('storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('route')) {
    function route($name, $parameters = [])
    {
        $routes = Route::getRegisteredRoutes();

        if (isset($routes[$name])) {
            $route = $routes[$name];
            if (!empty($parameters)) {
                foreach ($parameters as $key => $value) {
                    $route = str_replace('{' . $key . '}', $value, $route);
                }
            }

            return $route;
        }

        throw new \Exception("Route {$name} not defined.");
    }
}

if (!function_exists('now')) {
    function now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return env('APP_URL') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('response')) {
    function response()
    {
        return new class () {
            public function json($data, $status = 200, $headers = [])
            {
                http_response_code($status);
                header('Content-Type: application/json');

                foreach ($headers as $key => $value) {
                    header("$key: $value", false, $status);
                }

                echo json_encode($data);
                exit;
            }
        };
    }
}

if (!function_exists('greeting')) {
    function greeting()
    {
        $time = date('H');
        if ($time < '5') {
            return 'Subuh';
        } elseif ($time >= '5' && $time < '12') {
            return 'Pagi';
        } elseif ($time >= '12' && $time < '16') {
            return 'Siang';
        } elseif ($time >= '16' && $time < '19') {
            return 'Sore';
        } else {
            return 'Malam';
        }
    }
}