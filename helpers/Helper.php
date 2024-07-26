<?php

use Nettixcode\Framework\Libraries\Sources\Facades\Config;

if (!function_exists('Config'))
{
    function Config()
    {
        return Config::class;
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : dirname(__DIR__, 4);

        return $documentRoot . ($path ? '/' . ltrim($path, '/') : $path);
    }
}

if (!function_exists('root_dir')) {
    function root_dir($path = '')
    {
        // Gunakan $_SERVER['DOCUMENT_ROOT'] jika tersedia
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : dirname(__DIR__, 4);

        // Jika DOCUMENT_ROOT mengarah ke 'public', naik satu tingkat ke root utama
        if (is_dir($documentRoot . '/public')) {
            $documentRoot = dirname($documentRoot);
        }

        return $documentRoot . ($path ? '/' . ltrim($path, '/') : $path);
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