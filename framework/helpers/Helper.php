<?php

use Nettixcode\Framework\Libraries\Sources\Facades\Config;
use Nettixcode\Framework\Libraries\Sources\Facades\User;

if (!function_exists('Config'))
{
    function Config()
    {
        return Config::class;
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . ($path ? '/' . ltrim($path, '/') : $path);
    }
}

if (!function_exists('route')) {
    function route($name, $parameters = [])
    {
        $routes = Route::getRegisteredRoutes();

        if (isset($routes[$name])) {
            $route = $routes[$name];
            if (!empty($parameters)) {
                foreach ($parameters as $key => $value) {
                    $route = str_replace('{' . $key . '}', $value, $route);
                }
            }

            return $route;
        }

        throw new \Exception("Route {$name} not defined.");
    }
}

if (!function_exists('now')) {
    function now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return env('APP_URL') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('response')) {
    function response()
    {
        return new class () {
            public function json($data, $status = 200, $headers = [])
            {
                http_response_code($status);
                header('Content-Type: application/json');

                foreach ($headers as $key => $value) {
                    header("$key: $value", false, $status);
                }

                echo json_encode($data);
                exit;
            }
        };
    }
}

// if (!function_exists('sessionManager')) {
//     function sessionManager($key = null, $default = null)
//     {
//         return SessionManager::get($key, $default);
//     }
// }

if (!function_exists('greeting')) {
    function greeting()
    {
        $time = date('H');
        if ($time < '5') {
            return 'Subuh';
        } elseif ($time >= '5' && $time < '12') {
            return 'Pagi';
        } elseif ($time >= '12' && $time < '16') {
            return 'Siang';
        } elseif ($time >= '16' && $time < '19') {
            return 'Sore';
        } else {
            return 'Malam';
        }
    }
}

if (!function_exists('userStatus')) {
    function userStatus($str, $custom = false)
    {
        switch($str) {
            case 2:
                return $custom ? '<span class="label text-danger d-flex"><span class="dot-label bg-danger me-1"></span>Inactive</span>' : 'Inactive';
            case 1:
                return $custom ? '<span class="label text-success d-flex"><span class="dot-label bg-success me-1"></span>Active</span>' : 'Active';
        }
    }
}

if (!function_exists('tableRoleOptions')) {
    function tableRoleOptions($id, $name)
    {
        if ($name === 'admin' || $name === 'user') {
            return
                '<a class="btn btn-sm btn-light disabled"><i class="ri-edit-line ms-1 me-1"></i>
                </a>
                <a class="btn btn-sm btn-light disabled"><i class="ri-delete-bin-line ms-1 me-1"></i>
                </a>';
        } else {
            return
                '<a id="edit-role" role="button" class="btn btn-sm btn-info" data-id="' . $id . '" data-text="Edit Role" title="Edit Role" data-actions="edit-role"><i class="ri-edit-line ms-1 me-1 text-light"></i>
                </a>
                <a id="delete-role" role="button" class="btn btn-sm btn-danger" data-id="' . $id . '" data-text="Delete Role" title="Delete Role" data-actions="delete-role"><i class="ri-delete-bin-line ms-1 me-1 text-light"></i>
                </a>';
        }
    }
}

if (!function_exists('tableUserOptions')) {
    function tableUserOptions($id)
    {
        if (User::has('role','admin')) {
            return '
                <a id="edit-users" role="button" class="btn btn-sm btn-info" data-id="' . $id . '" data-text="Edit Users" title="Edit Users" data-actions="edit-users"><i class="ri-edit-line ms-1 me-1 text-light"></i>
                </a>
                <a id="edit-image" role="button" class="btn btn-sm btn-primary" data-id="' . $id . '" data-text="Edit Image" title="Edit Image" data-actions="edit-image"><i class="ri-image-line ms-1 me-1 text-light"></i>
                </a>
                <a id="edit-pass" role="button" class="btn btn-sm btn-warning" data-id="' . $id . '" data-text="Edit Pass" title="Edit Pass" data-actions="edit-pass"><i class="ri-key-line ms-1 me-1 text-light"></i>
                </a>
                <a id="delete-users" role="button" class="btn btn-sm btn-danger" data-id="' . $id . '" data-text="Delete Users" title="Delete Users" data-actions="delete-users"><i class="ri-delete-bin-line ms-1 me-1 text-light"></i>
                </a>';
        } else {
            if (sessionManager::get('id') == $id) {
                return '
                    <a id="edit-users" role="button" class="btn btn-sm btn-info" data-id="' . $id . '" data-text="Edit Users" title="Edit Users" data-actions="edit-users"><i class="ri-edit-line ms-1 me-1 text-light"></i>
                    </a>
                    <a id="edit-image" role="button" class="btn btn-sm btn-primary" data-id="' . $id . '" data-text="Edit Image" title="Edit Image" data-actions="edit-image"><i class="ri-image-line ms-1 me-1 text-light"></i>
                    </a>
                    <a id="edit-pass" role="button" class="btn btn-sm btn-warning" data-id="' . $id . '" data-text="Edit Pass" title="Edit Pass" data-actions="edit-pass"><i class="ri-key-line ms-1 me-1 text-light"></i>
                    </a>';
            } else {
                return '
                    <a id="not-allowed" role="button" class="btn btn-sm btn-danger" data-text="Not Allowed"><i class="ri-lock-2-line ms-1 me-1 text-light"></i>
                    </a>';
            }
        }
    }
}
