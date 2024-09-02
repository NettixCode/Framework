<?php

use Nettixcode\Framework\Foundation\Application;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Facades\NxLog;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Encryption\Encrypter;
use Firebase\JWT\JWT;

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

if (! function_exists('generateJwtToken')) {
    function generateJwtToken(){
        if (!env('JWT_AUTH')) {
            return;
        }

        if (!SessionManager::get('jwt_token')) {
            $current_uri = $_SERVER['REQUEST_URI'];

            $userId  = SessionManager::get('id') ? SessionManager::get('id') : null;
            $userNm  = SessionManager::get('username') ? SessionManager::get('username') : null;
            $payload = [
                'iss'  => env('APP_URL'), // Issuer
                'iat'  => time(), // Issued at
                'exp'  => time() + env('JWT_EXPIRATION_TIME'), // Expiration time
                'sub'  => $userId, // Subject (user ID)
                'name' => $userNm,
            ];

            $jwt = JWT::encode($payload, getSecret(), 'HS256');

            $encrypter = new Encrypter(base64_decode(substr(app('config')->get('app.key'), 7)), 'AES-256-CBC');
            $encryptedJwt = $encrypter->encrypt($jwt);

            SessionManager::set('jwt_token', $encryptedJwt);
            NxLog::info('JWT Token Generated');
        }
    }

    function getSecret()
    {
        $key = env('APP_KEY');
        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }
        return $key;
    }
}

if (! function_exists('create_csrf_token')) {
    function create_csrf_token()
    {
        $current_uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (!SessionManager::has('_token')) {
            SessionManager::set('_token', generateToken());
        }

        if (isStaticFile($current_uri)) {
            return;
        }
    }

    function generateToken()
    {
        $key = env('APP_KEY');
        
        if ($key !== null && strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', session_id(), $key ?: 'fallback_key');
    }

    function isStaticFile($uri)
    {
        $staticFileExtensions = ['.css', '.js', '.map', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
        foreach ($staticFileExtensions as $extension) {
            if (strpos($uri, $extension) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('regenerate_csrf_token')) {
    function regenerate_csrf_token()
    {
        SessionManager::forget('_token');

        create_csrf_token();

        return SessionManager::get('_token');
    }
}

if (! function_exists('session')) {
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array<string, mixed>|string|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \Illuminate\Config\Repository : ($key is string ? mixed : null))
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string|null  $basePath
     * @param  string  ...$paths
     * @return string
     */
    function join_paths($basePath, ...$paths)
    {
        foreach ($paths as $index => $path) {
            if (empty($path) && $path !== '0') {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
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

if (! function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return base_path('resources') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

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

if (! function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string|null  $path
     * @param  mixed  $parameters
     * @param  bool|null  $secure
     * @return ($path is null ? \Illuminate\Contracts\Routing\UrlGenerator : string)
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        $urlGenerator = app('url');
        if (is_null($path)) {
            return $urlGenerator;
        }

        return $urlGenerator->to($path, $parameters, $secure);
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

            public function view($view, $data = [], $status = 200, $headers = [])
            {
                http_response_code($status);
                header('Content-Type: text/html');

                foreach ($headers as $key => $value) {
                    header("$key: $value", false, $status);
                }

                $factory = app('view');
                $viewInstance = $factory->make($view, $data, $headers);
                echo $viewInstance->render();
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
                    if (isset($debugbar['time'])) {
                        $debugbar['time']->startMeasure('Page Render', 'Page Render','time');
                    }
                }
                break;
            case 'exceptions':
                if (isset($debugbar)) {
                    $debugbar['exceptions']->addException($message);
                }
                break;
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