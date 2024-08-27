<?php

namespace Nettixcode\Framework\Middleware;

use Nettixcode\Framework\Facades\Config;
use Nettixcode\Framework\Foundation\Manager\SessionManager;
use Nettixcode\Framework\Facades\User;
use Nettixcode\Framework\Facades\NxLog;

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

        if (!SessionManager::has('isLogin') || !SessionManager::has('id')) {
            return $next($request);
        }

        if (!User::has('role', 'admin')) {
            if (in_array($currentUri, $restrictedPages)) {
                header('Location: /dashboard');
                NxLog::alert('Someone with id ' . SessionManager::get('id') . ' trying to access restricted area : ' .$currentUri);
                exit();
            }

            if (in_array($currentUri, $restrictedActions)) {
                echo json_encode(['success' => false, 'errors' => 'You do not have permission to do this action.']);
                NxLog::alert('Someone with id ' . SessionManager::get('id') . ' trying to access restricted action : ' .$currentUri);
                exit();
            }
        }

        NxLog::info('Check Admin Restricted');
        
        return $next($request);
    }
}
