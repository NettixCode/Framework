<?php

namespace Nettixcode\Framework\Middleware;

use Firebase\JWT\JWT;
use Illuminate\Encryption\Encrypter;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\NxLog;

class GenerateJwtToken
{
    public static function handle($request, $next)
    {
        if (!env('JWT_AUTH')) {
            return $next($request);
        }

        if (!SessionManager::get('jwt_token')) {
            $current_uri = $_SERVER['REQUEST_URI'];

            if (self::isStaticFile($current_uri)) {
                return $next($request);
            }

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

            $encrypter = new Encrypter(base64_decode(substr(app('config')->get('app.key'), 7)), 'AES-256-CBC');
            $encryptedJwt = $encrypter->encrypt($jwt);

            SessionManager::set('jwt_token', $encryptedJwt);
            setcookie('jwt_token', $encryptedJwt, time() + 3600, '/', '', false, true);
            NxLog::info('JWT Token Generated');
        }
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
