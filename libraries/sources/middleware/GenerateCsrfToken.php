<?php

namespace Nettixcode\Framework\Libraries\Sources\Middleware;

use Nettixcode\Framework\Libraries\SessionManager;

class GenerateCsrfToken
{
    public static function handle($request, $next)
    {
        if (!env('CSRF_AUTH')) {
            return $next($request);
        }

        $current_uri = $_SERVER['REQUEST_URI'];

        if (!sessionManager::has('_token')) {
            sessionManager::set('_token', self::generateToken());
        }

        // Jangan lakukan pengecekan untuk file statis
        if (self::isStaticFile($current_uri)) {
            return $next($request);
        }

        return $next($request);
    }

    public static function modifyOutput($output, $request)
    {
        if (!sessionManager::has('_token')) {
            return $output;
        }

        if (is_string($output) && !empty($output)) {
            $output = self::insertToken($output);
        }

        return $output;
    }

    private static function insertToken($html)
    {
        $token       = sessionManager::get('_token');
        $hiddenInput = '<input type="hidden" name="_token" value="' . $token . '">';

        // Add the hidden input to every form tag in the HTML
        $html = preg_replace('/<form\b([^>]*)>/i', '<form$1>' . $hiddenInput, $html);

        // Log to debug
        // if (strpos($html, $hiddenInput) !== false) {
        //     error_log('CSRF Created: ' . $_SERVER['REQUEST_URI']);
        // } else {
        //     error_log("CSRF didn't need.");
        // }

        return $html;
    }

    private static function generateToken()
    {
        $key = env('APP_KEY');
        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', session_id(), $key);
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
