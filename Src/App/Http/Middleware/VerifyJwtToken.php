<?php

namespace Nettixcode\Framework\Middleware;

use Nettixcode\Framework\Facades\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Encryption\Encrypter;
use Nettixcode\Framework\Facades\NxLog;
use SessionManager;

class VerifyJwtToken
{
    public static function handle($request, $next)
    {
        if (!env('JWT_AUTH')) {
            return $next($request);
        }

        if (!session()->has('isLogin')){
            return $next($request);
        }

        $current_uri    = $_SERVER['REQUEST_URI'];
        $apiPrefix = $_COOKIE['apiPrefix'];
        $defaultExclude = [
            // '/signout',
            // '/'.$apiPrefix.'/json/role-permission',
            // '/'.$apiPrefix.'/submit/page-builder/save',
        ];
        $excludedConfig = Config::get('middleware.token.EXCLUDE_FROM_TOKEN');
        $excludedRoutes = array_merge($defaultExclude, $excludedConfig);
        $excludedRoutes = array_unique($excludedRoutes);

        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return $next($request);
        }

        // Jangan lakukan pengecekan jika URI ada dalam daftar pengecualian
        if (in_array($current_uri, $excludedRoutes)) {
            return $next($request);
        }

        $jwt = null;

        // Periksa token di header Authorization
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $jwt = $matches[1];
                $jwt = urldecode($jwt);
                // NxLog::alert('JWT FROM HEADER = '.$jwt);
            }
        }

        // Jika tidak ada di header, periksa di cookie
        if (!$jwt && isset($_COOKIE['jwt_token'])) {
            $jwt = $_COOKIE['jwt_token'];
        }

        // Jika tidak ada token di kedua tempat, tolak akses
        if (!$jwt) {
            try {
                if ($current_uri === '/signin' || $current_uri === '/signout') {
                    return $next($request);
                } else {
                    throw new \Exception('Unauthorized', 401);
                }
            } catch (\Exception $e) {
                $handler = app()->exceptions;
                http_response_code(401);
                $handler->report($e);
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['message' => $e->getMessage()]);
                exit();
            }
        }

        try {
            // Mendekripsi token sebelum verifikasi
            $encrypter = new Encrypter(base64_decode(substr(app('config')->get('app.key'), 7)), 'AES-256-CBC');
            $decryptedJwt = $encrypter->decrypt($jwt);

            // Verifikasi JWT yang telah didekripsi
            $decoded = JWT::decode($decryptedJwt, new Key(self::getSecret(), 'HS256'));
            NxLog::info('JWT Token Verified');
            // Token valid, lanjutkan ke middleware atau controller berikutnya
            return $next($request);
        } catch (\Exception $e) {
            $handler = app()->exceptions;
            http_response_code(401);
            $handler->report($e);
            NxLog::alert('JWT NOT Verified: ' . $request->getUri() . ' - ' . $e->getMessage());
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
