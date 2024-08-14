<?php

namespace Nettixcode\Framework\Middleware;

use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\User;

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

        $restrictedPages   = array_merge($defaultRestrictedPages, Config::get('middleware.admin_restricted.pages'));
        $restrictedActions = array_merge($defaultRestrictedActions, Config::get('middleware.admin_restricted.actions'));

        // Handle pages signin and signout because there is no session yet
        if (!SessionManager::has('isLogin') || !SessionManager::has('id')) {
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

        // error_log("CHECK ADMIN");

        return $next($request);
    }
}
