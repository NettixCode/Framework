<?php

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\Config;
use Illuminate\Contracts\View\Factory as ViewFactory;

if (!function_exists('app')) {
    function app($abstract = null)
    {
        $app = Application::getInstance();

        if (is_null($abstract)) {
            return $app;
        }

        try {
            return $app->make($abstract);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (! function_exists('view')) {
    function view($view = null, $data = [], $mergeData = [])
    {
        global $debugbar;
        $factory = app('view');
        if (isset($debugbar)) {
            $data['debugbarHead'] = $debugbar->getJavascriptRenderer()->renderHead();
            $data['debugbarRender'] = $debugbar->getJavascriptRenderer()->render();
        } else {
            $data['debugbarHead'] = '';
            $data['debugbarRender'] = '';
        }

        if (func_num_args() === 0) {
            return $factory;
        }

        $viewInstance = $factory->make($view, $data, $mergeData);
        
        echo $viewInstance->render();
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    function csrf_token()
    {
        return SessionManager::get('_token');
    }
}

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

// if (!function_exists('route')) {
//     function route($name, $parameters = [])
//     {
//         $filePath = Config::get('app.paths.storage_path') . '/registered_routes.json';
//         $routes = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : null;

//         if (isset($routes[$name])) {
//             $route = $routes[$name];
//             if (!empty($parameters)) {
//                 foreach ($parameters as $key => $value) {
//                     $route = str_replace('{' . $key . '}', $value, $route);
//                 }
//             }

//             return $route;
//         }

//         throw new \Exception("Route {$name} not defined.");
//     }
// }

if (! function_exists('route')) {
    /**
     * Generate the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     */
    function route($name, $parameters = [], $absolute = true)
    {
        return app('url')->route($name, $parameters, $absolute);
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
                global $debugbar;

                // Cek apakah aksi yang sedang dijalankan adalah closure atau serializable closure
                $isClosure = false;
                if (function_exists('app') && app()->bound('router')) {
                    $currentRoute = app('router')->current();
                    if ($currentRoute) {
                        $action = $currentRoute->getAction();

                        // Pengecekan apakah uses adalah Closure atau serialized Closure
                        if (isset($action['uses'])) {
                            if ($action['uses'] instanceof \Closure) {
                                // Jika langsung Closure
                                $isClosure = true;
                            } elseif (is_string($action['uses']) && strpos($action['uses'], 'SerializableClosure') !== false) {
                                // Jika uses adalah string yang mengandung 'SerializableClosure'
                                $isClosure = true;
                            }
                        }
                    }
                }

                // Jika bukan closure atau serialized closure, tambahkan data debugbar
                if (!$isClosure && isset($debugbar)) {
                    $data['debugbar'] = $debugbar->getJavascriptRenderer()->render(false);
                    $data['debugbar_data'] = $debugbar->getData();
                }
                
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

if (!function_exists('debug_send')) {
    function debug_send($tipe,$message){
        global $debugbar;
        switch ($tipe) {
            case 'messages':
                if (isset($debugbar)) {
                    $debugbar['messages']->addMessage($message);
                }
                break;
            case 'exceptions':
                if (isset($debugbar)) {
                    $debugbar['exceptions']->addException($message);
                }
                break;
        }
        
        if (isset($debugbar) && isset($debugbar['time'])) {
            $debugbar['time']->startMeasure('Page Render', 'Page Render','time');
        }
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