<?php

namespace Nettixcode\Framework\Middleware;

use Nettixcode\Framework\Facades\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class VerifyJwtToken
{
    public static function handle($request, $next)
    {
        if (!env('JWT_AUTH')) {
            return $next($request);
        }

        $current_uri    = $_SERVER['REQUEST_URI'];
        $defaultExclude = [
            '/signout',
            '/api/json/role-permission',
            '/api/submit/page-builder/save'
        ];
        $excludedConfig = Config::get('middleware.token.EXCLUDE_FROM_TOKEN');
        $excludedRoutes = array_merge($defaultExclude,$excludedConfig);
        $excludedRoutes = array_unique($excludedRoutes);

        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return $next($request);
        }

        if (in_array($current_uri, $excludedRoutes)) {
            return $next($request);
        }

        $jwt = null;

        // Periksa token di header Authorization
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $jwt = $matches[1];
            }
        }

        // Jika tidak ada di header, periksa di cookie
        if (!$jwt && isset($_COOKIE['jwt_token'])) {
            $jwt = $_COOKIE['jwt_token'];
        }

        // Jika tidak ada token di kedua tempat, tolak akses
        if (!$jwt) {
            if ($current_uri === '/signin' || $current_uri === '/signout') {
                return $next($request);
            } else {
                // Redirect to login page if JWT token is not set
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['message' => 'Unauthorized']);
                exit();
            }
        }

        try {
            $decoded = JWT::decode($jwt, new Key(self::getSecret(), 'HS256'));
            // Token is valid, continue to the next middleware or controller
            error_log('JWT Verified: ' . $request->getUri());

            return $next($request);
        } catch (\Exception $e) {
            // Token is invalid, redirect to login page
            error_log('JWT NOT Verified: ' . $request->getUri() . ' - ' . $e->getMessage());
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['message' => 'Unauthorized']);
            exit();
        }
    }

    private static function getSecret()
    {
        $key = env('APP_KEY');
        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
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
