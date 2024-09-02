<?php

namespace Nettixcode\Framework\Middleware;

use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\NxLog;

class AuthSession
{
    public function handle($request, $next)
    {
        $current_uri    = $_SERVER['REQUEST_URI'];
        $signin_page    = '/signin';
        $login_api      = '/api/submit/signin';
        $signout_page   = '/signout';
        $dashboard_page = '/dashboard';
        $users_page     = '/account';

        // Jangan lakukan pengecekan login untuk halaman signin atau login API dengan query parameter 'login'
        if ($current_uri === $login_api || $current_uri === $signout_page || $current_uri === '/' || $current_uri === '/index') {
            return $next($request);
        }

        // Jika pengguna belum login dan bukan di halaman signin, arahkan ke halaman signin
        if (!SessionManager::has('isLogin') && $current_uri !== $signin_page) {
            header("Location: $signin_page");
            exit();
        }

        // Jika pengguna sudah login tapi incomplete, arahkan ke halaman users
        if (SessionManager::has('isLogin') && SessionManager::get('isLogin') && SessionManager::has('incomplete') && SessionManager::get('incomplete')) {
            if ($current_uri !== $users_page) {
                setcookie('incomplete', 'true', 0, '/');
                header("Location: $users_page");
                exit();
            }
        }

        // Jika pengguna sudah login dan mencoba mengakses halaman signin, arahkan ke halaman dashboard
        if (SessionManager::has('isLogin') && SessionManager::get('isLogin') && $current_uri === $signin_page) {
            header("Location: $dashboard_page");
            exit();
        }

        // Jika pengguna login tidak valid, arahkan ke halaman signout
        if (SessionManager::has('isLogin') && !SessionManager::get('isLogin')) {
            header("Location: $signout_page");
            exit();
        }

        NxLog::info('Check Login Status Running');
        // Lanjutkan ke middleware berikutnya atau kontroler
        return $next($request);
    }
}
