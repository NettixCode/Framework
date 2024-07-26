<?php

namespace Nettixcode\Framework\Libraries\Sources\Middleware;

use Firebase\JWT\JWT;
use Nettixcode\Framework\Libraries\SessionManager;

class GenerateJwtToken
{
    public static function handle($request, $next)
    {
        if (!env('JWT_AUTH')) {
            return $next($request);
        }

        $current_uri = $_SERVER['REQUEST_URI'];
        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return $next($request);
        }

        // Anggap Anda sudah memiliki user_id dari proses login
        $userId  = SessionManager::get('id') ? SessionManager::get('id') : null;
        $userNm  = SessionManager::get('username') ? SessionManager::get('username') : null;
        $payload = [
            'iss'  => env('APP_URL'), // Issuer
            'iat'  => time(), // Issued at
            'exp'  => time() + env('JWT_EXPIRATION_TIME'), // Expiration time
            'sub'  => $userId, // Subject (user ID)
            'name' => $userNm,
        ];

        $jwt = JWT::encode($payload, self::getSecret(), 'HS256');
        SessionManager::set('jwt_token', $jwt);
        setcookie('jwt_token', $jwt, time() + 3600, '/', '', false, false);
        // error_log('JWT token generated:' . $request->getUri());

        return $next($request);
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
