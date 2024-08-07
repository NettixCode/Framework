<?php

namespace Nettixcode\Framework\Controllers;

use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Facades\NxEngine;
use Nettixcode\Framework\Models\Permission;
use Nettixcode\Framework\Models\Role;
use Nettixcode\Framework\Facades\User;

class RolePermissionController
{
    public function index()
    {
        User::has('role','admin') ?
        NxEngine::view('admin.role-permission'):
        NxEngine::view('errors.error-403');
    }

    public function table_role(Request $request)
    {
        if ($request->has('table_role')) {
            $result = Role::datatable();

            $json = [
                'draw'            => 1,
                'recordsTotal'    => count($result),
                'recordsFiltered' => count($result),
                'data'            => $result,
            ];

            return response()->json($json);
        } else {
            NxEngine::redirectToErrorPage();
        }
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

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name'        => 'required|string|lowercase',
                'description' => 'required|string',
            ]);

            if (!user::has('role', 'admin')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('role_create')) {
                $data = [
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                ];
                $data_exist = [
                    'delimeter' => 'AND',
                    'name'      => $request->input('username'),
                ];

                return Role::create($data)->exist($data_exist)->save();
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()]);
        } catch (\Exception $e) {
            $errors = json_decode($e->getMessage(), true);

            return response()->json(['success' => false, 'message' => $errors]);
        }
    }

    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id'          => 'required|numeric',
                'name'        => 'required|string|lowercase',
                'description' => 'required|string',
            ]);

            if (!user::has('role', 'admin')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('role_edit')) {
                $data = [
                    'id'          => $request->input('id'),
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                ];
                $data_exist = [
                    'delimeter' => 'AND',
                    'name'      => $request->input('name'),
                ];

                return Role::edit($data)->exist($data_exist)->save();
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()]);
        } catch (\Exception $e) {
            $errors = json_decode($e->getMessage(), true);

            return response()->json(['success' => false, 'message' => $errors]);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|numeric',
            ]);

            if (!user::has('role', 'admin')) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('roles_delete')) {
                $data = [
                    'id' => $request->input('id'),
                ];

                return Role::remove($data);
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()]);
        } catch (\Exception $e) {
            $errors = json_decode($e->getMessage(), true);

            return response()->json(['success' => false, 'message' => $errors]);
        }
    }
}
