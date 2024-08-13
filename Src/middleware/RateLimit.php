<?php

namespace Nettixcode\Framework\Middleware;

use Closure;
use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Foundation\Manager\SessionManager;

class RateLimit
{
    public static function handle($request, Closure $next)
    {
        $ip         = $_SERVER['REMOTE_ADDR'];
        $key        = 'rate_limit:' . $ip;
        $config     = Config::get('app.rate_limit');
        $rateLimit  = $config['requests']; // Max requests
        $retryAfter = $config['time_frame']; // Time frame in seconds

        if (!SessionManager::has($key)) {
            SessionManager::set($key, ['count' => 0, 'expires' => time() + $retryAfter]);
        }

        $data = SessionManager::get($key);

        if ($data['count'] >= $rateLimit) {
            if (time() < $data['expires']) {
                header('Retry-After: ' . ($data['expires'] - time()));
                http_response_code(429);
                echo 'Rate limit exceeded';
                self::logRequest($ip, 'Rate limit exceeded');
                exit;
            } else {
                SessionManager::set($key, ['count' => 0, 'expires' => time() + $retryAfter]);
            }
        }

        $data['count'] += 1;
        SessionManager::set($key, $data);

        self::logRequest($ip, 'Request allowed');

        // error_log("RATE LIMIT");
        return $next($request);
    }

    private static function logRequest($ip, $message)
    {
        $logMessage = sprintf("[%s] IP: %s - %s\n", date('Y-m-d H:i:s'), $ip, $message);
        file_put_contents(Config::get('app.files.ratelimitlogs'), $logMessage, FILE_APPEND);
    }
}
