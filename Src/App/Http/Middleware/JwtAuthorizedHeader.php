<?php

namespace Nettixcode\Framework\Middleware;
use Nettixcode\Framework\Facades\NxLog;
use SessionManager;

class JwtAuthorizedHeader
{
    public function handle($request, $next)
    {
        if (session()->has('jwt_token')) {
            $jwt = session('jwt_token');
            $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $jwt";
            // NxLog::info("Authorization header set: Bearer $jwt");
        }

        return $next($request);
    }
}
