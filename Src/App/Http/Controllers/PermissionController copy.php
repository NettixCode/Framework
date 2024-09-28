<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Nettixcode\App\Models\Permission;
use Nettixcode\Facades\User;

class PermissionController
{
    public function index()
    {
        return Auth::user()->isAdmin() ?
        view('admin.permission'):
        abort(403);
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
            new \Exception('???',500);
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
