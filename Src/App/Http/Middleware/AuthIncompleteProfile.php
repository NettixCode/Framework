<?php

namespace Nettixcode\App\Http\Middleware;

use Illuminate\Support\Facades\Log;

class AuthIncompleteProfile
{
    public function handle($request, $next)
    {
        // $apiPrefix      = $_COOKIE['apiPrefix'] ?? 'api';
        $current_uri    = $_SERVER['REQUEST_URI'];
        // $signin_page    = '/login';
        // $login_api      = '/'.$apiPrefix.'/submit/login';
        // $signout_page   = '/logout';
        // $dashboard_page = '/dashboard';
        $users_page     = '/account';

        // Jangan lakukan pengecekan login untuk halaman signin atau login API dengan query parameter 'login'
        // if ($current_uri === $login_api || $current_uri === $signout_page || $current_uri === '/' || $current_uri === '/index') {
        //     return $next($request);
        // }

        // Jika pengguna belum login dan bukan di halaman signin, arahkan ke halaman signin
        // if (!session()->has('isLogin') && $current_uri !== $signin_page) {
        //     header("Location: $signin_page");
        //     exit();
        // }

        // Jika pengguna sudah login tapi incomplete, arahkan ke halaman users
        if ($request->method() === 'GET') {
            if (session()->has('isLogin') && session('isLogin') && session()->has('incomplete') && session('incomplete')) {
                if ($current_uri !== $users_page) {
                    setcookie('incomplete', 'true', 0, '/');
                    header("Location: $users_page");
                    exit();
                }
            }
        }

        // Jika pengguna sudah login dan mencoba mengakses halaman signin, arahkan ke halaman dashboard
        // if (session()->has('isLogin') && session('isLogin') && $current_uri === $signin_page) {
        //     header("Location: $dashboard_page");
        //     exit();
        // }

        // Jika pengguna login tidak valid, arahkan ke halaman signout
        // if (session()->has('isLogin') && !session('isLogin')) {
        //     header("Location: $signout_page");
        //     exit();
        // }

        // Log::info('Auth Session Redirector Running');
        return $next($request);
    }
}
