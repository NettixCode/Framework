<?php

namespace Nettixcode\Framework\Libraries\Sources\Middleware;

use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Libraries\SessionManager;

class VerifyCsrfToken
{
    public static function handle($request, $next)
    {
        if (!env('CSRF_AUTH')) {
            return $next($request);
        }

        $current_uri    = $_SERVER['REQUEST_URI'];
        $defaultExclude = [
            '/signout',
            '/api/json/role-permission',
            '/api/submit/page-builder/save',
        ];
        $excludedConfig = Config::load('middleware', 'token.EXCLUDE_FROM_TOKEN');
        $excludedRoutes = array_merge($defaultExclude,$excludedConfig);
        $excludedRoutes = array_unique($excludedRoutes);

        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return $next($request);
        }

        if (in_array($current_uri, $excludedRoutes)) {
            return $next($request);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!$token || $token !== SessionManager::get('_token')) {
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
        }

        // error_log('CSRF Verified: ' . $request->getUri());

        return $next($request);
    }

    private static function isStaticFile($uri)
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
