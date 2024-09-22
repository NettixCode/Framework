<?php

namespace Nettixcode\Framework\Controllers;

use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Facades\NxEngine;
use Nettixcode\Framework\Models\Permission;
use Nettixcode\Framework\Facades\User;

class PermissionController
{
    public function index()
    {
        debug_send('messages','viewing permission pages.');
        User::has('role','admin') ?
        NxEngine::view('admin.permission'):
        NxEngine::view('errors.error-403');
    }

    public function table_permission(Request $request)
    {
        if ($request->has('table_permission')) {
            $result = Permission::datatable();

            $json = [
                'draw'            => 1,
                'recordsTotal'    => count($result['data']),
                'recordsFiltered' => count($result['data']),
                'data'            => $result['data'],
                'roles'           => $result['roles'], // Menambahkan roles untuk digunakan di client-side
            ];

            return response()->json($json);
        } else {
            NxEngine::redirectToErrorPage();
        }
    }

    public function update_permission()
    {
        $data    = json_decode(file_get_contents('php://input'), true);
        $changes = $data['changes'];
        $result  = Permission::updates($changes);

        return response()->json($result);
    }
}