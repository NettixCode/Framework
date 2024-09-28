<?php

namespace Nettixcode\app\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Nettixcode\App\Models\Role;
use Nettixcode\Facades\User;
use Illuminate\Validation\ValidationException;

class RoleController
{
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return Auth::user()->isAdmin() ?
        view('admin.roles', compact('roles')):
        abort(403);
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
            new \Exception('???',500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name'        => 'required|string|lowercase',
                'description' => 'required|string',
                'permissions' => 'array',
                'permissions.*' => 'numeric'
            ]);

            if (!Auth::user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('role_create')) {
                $data = [
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                ];
                $data_exist = [
                    'delimeter' => 'AND',
                    'name'      => $request->input('name'),
                ];

                return Role::create($data)
                    ->exist($data_exist)
                    ->withPermissions($request->input('permissions', []))
                    ->save();
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
                'permissions' => 'array',
                'permissions.*' => 'numeric'
            ]);

            if (!Auth::user()->isAdmin()) {
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

                return Role::edit($data)
                    ->exist($data_exist)
                    ->withPermissions($request->input('permissions', []))
                    ->save();
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

            if (!Auth::user()->isAdmin()) {
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
