<?php

namespace Nettixcode\App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Nettixcode\Facades\User;

class AdminRestricted
{
    public function handle($request, $next)
    {
        $currentUri = $_SERVER['REQUEST_URI'];

        $defaultRestrictedPages = [
            '/users',
            '/roles',
            '/permission',
            '/page-builder',
        ];
        $defaultRestrictedActions = [
            '/api/submit/users/create',
            '/api/submit/users/delete',
            '/api/submit/roles/create',
            '/api/submit/roles/update',
            '/api/submit/roles/delete',
            '/api/submit/role-permission/update-permission',
            '/api/submit/page-builder/save',
        ];

        $restrictedPages   = array_merge($defaultRestrictedPages, Config::get('middleware.admin_restricted.pages'));
        $restrictedActions = array_merge($defaultRestrictedActions, Config::get('middleware.admin_restricted.actions'));

        if (!session()->has('isLogin') || !session()->has('id')) {
            return $next($request);
        }

        if (!User::hasRole('admin')) {
            if (in_array($currentUri, $restrictedPages)) {
                header('Location: /dashboard');
                NxLog::alert('Someone with id ' . session('id') . ' trying to access restricted area : ' .$currentUri);
                exit();
            }

            if (in_array($currentUri, $restrictedActions)) {
                echo json_encode(['success' => false, 'errors' => 'You do not have permission to do this action.']);
                NxLog::alert('Someone with id ' . session('id') . ' trying to access restricted action : ' .$currentUri);
                exit();
            }
        }

        NxLog::info('Check Admin Restricted');

        return $next($request);
    }
}
