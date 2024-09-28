<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Nettixcode\App\Models\Permission;

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
                'table_name'  => 'required|string',
                'description' => 'required|string',
            ]);

            if (!Auth::user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('permission_create')) {
                $data = [
                    'name'        => $request->input('name'),
                    'table_name'  => $request->input('table_name'),
                    'description' => $request->input('description'),
                ];
                $data_exist = [
                    'delimeter' => 'AND',
                    'name'      => $request->input('name'),
                ];

                return Permission::create($data)
                    ->exist($data_exist)
                    ->save();
            }
        } catch (\ValidationException $e) {
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
                'table_name'  => 'required|string',
                'description' => 'required|string',
            ]);

            if (!Auth::user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action']);
            }

            if ($request->has('permission_edit')) {
                $data = [
                    'id'          => $request->input('id'),
                    'name'        => $request->input('name'),
                    'table_name'  => $request->input('table_name'),
                    'description' => $request->input('description'),
                ];
                $data_exist = [
                    'delimeter' => 'AND',
                    'name'      => $request->input('name'),
                ];

                return Permission::edit($data)
                    ->exist($data_exist)
                    ->save();
            }
        } catch (\ValidationException $e) {
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

            if ($request->has('permissions_delete')) {
                $data = [
                    'id' => $request->input('id'),
                ];

                return Permission::remove($data);
            }
        } catch (\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()]);
        } catch (\Exception $e) {
            $errors = json_decode($e->getMessage(), true);

            return response()->json(['success' => false, 'message' => $errors]);
        }
    }
}
