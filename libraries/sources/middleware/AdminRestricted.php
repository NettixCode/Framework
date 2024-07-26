<?php

namespace Nettixcode\Framework\Libraries\Sources\Middleware;

use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Libraries\SessionManager;
use Nettixcode\Framework\Libraries\Sources\Facades\User;

class AdminRestricted
{
    public function handle($request, $next)
    {
        $currentUri = $_SERVER['REQUEST_URI'];

        $defaultRestrictedPages = [
            '/users',
            '/role-permission',
            '/page-builder',
        ];
        $defaultRestrictedActions = [
            '/api/submit/users/create',
            '/api/submit/users/delete',
            '/api/submit/role-permission/update-permission',
            '/api/submit/page-builder/save',
        ];

        $restrictedPages   = array_merge($defaultRestrictedPages, Config::load('middleware', 'admin_restricted.pages'));
        $restrictedActions = array_merge($defaultRestrictedActions, Config::load('middleware', 'admin_restricted.actions'));

        // Handle pages signin and signout because there is no session yet
        if (!sessionManager::has('isLogin') || !sessionManager::has('id')) {
            return $next($request);
        }

        if (!User::has('role', 'admin')) {
            // Check if the current URI is in the restricted pages
            if (in_array($currentUri, $restrictedPages)) {
                header('Location: /dashboard');
                exit();
            }

            // Check if the current URI is in the restricted actions
            if (in_array($currentUri, $restrictedActions)) {
                echo json_encode(['success' => false, 'errors' => 'You do not have permission to do this action.']);
                exit();
            }
        }

        return $next($request);
    }
}
