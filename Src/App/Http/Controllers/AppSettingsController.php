<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Nettixcode\Facades\User;

class AppSettingsController
{
    public function index()
    {
        return Auth::user()->isAdmin() ?
            view('admin.app-settings') :
            abort(403);
    }

    public function update(Request $request)
    {
        $keyChanged = false;
        $currentAppKey = env('APP_KEY');

        // Update .env file
        $envUpdate = [
            'APP_NAME' => $request->input('APP_NAME'),
            'APP_ENV' => $request->input('APP_ENV'),
            'APP_KEY' => $request->input('APP_KEY'),
            'APP_URL' => $request->input('APP_URL'),
            'APP_TIMEZONE' => $request->input('APP_TIMEZONE'),
            'APP_DEBUG' => $request->has('APP_DEBUG') ? 'true' : 'false',
            'APP_MAINTENANCE_DRIVER' => $request->input('APP_MAINTENANCE_DRIVER'),
            'CACHE_STORE' => $request->input('CACHE_STORE'),
            'CACHE_PREFIX' => $request->input('CACHE_PREFIX'),
            'DB_CONNECTION' => $request->input('DB_CONNECTION'),
            'DB_HOST' => $request->input('DB_HOST'),
            'DB_PORT' => $request->input('DB_PORT'),
            'DB_DATABASE' => $request->input('DB_DATABASE'),
            'DB_USERNAME' => $request->input('DB_USERNAME'),
            'DB_PASSWORD' => $request->input('DB_PASSWORD'),
            'SESSION_DRIVER' => $request->input('SESSION_DRIVER'),
            'SESSION_LIFETIME' => $request->input('SESSION_LIFETIME'),
            'SESSION_ENCRYPT' => $request->has('SESSION_ENCRYPT') ? 'true' : 'false',
            'SESSION_PATH' => $request->input('SESSION_PATH'),
            'SESSION_DOMAIN' => $request->input('SESSION_DOMAIN'),
            'SESSION_SECURE_COOKIE' => $request->has('SESSION_SECURE_COOKIE') ? 'true' : 'false',
            'SESSION_EXPIRE_ON_CLOSE' => $request->has('SESSION_EXPIRE_ON_CLOSE') ? 'true' : 'false',
        ];

        // Cek apakah APP_KEY berubah
        if ($currentAppKey !== $request->input('APP_KEY')) {
            $keyChanged = true;
        }

        // Update debugbar.php config
        // $debugbarConfig = $this->getDebugbarConfig();
        // $debugbarConfig['widgets'] = [
        //     'messages' => $request->has('widgets.messages'),
        //     'request' => $request->has('widgets.request'),
        //     'time' => $request->has('widgets.time'),
        //     'exceptions' => $request->has('widgets.exceptions'),
        //     'httpRequest' => $request->has('widgets.httpRequest'),
        //     'route' => $request->has('widgets.route'),
        //     'query' => $request->has('widgets.query'),
        //     'session' => $request->has('widgets.session'),
        //     'config' => $request->has('widgets.config'),
        // ];

        $configUpdated = $this->updateEnv($envUpdate);

        // Response logic
        $response = ['report' => 'error', 'message' => 'Failed to update settings.'];

        if ($configUpdated) {
            $response = ['report' => 'success', 'message' => 'Settings have been updated!'];
            if ($keyChanged) {
                $response['keyChange'] = true;
            }
        }

        return response()->json($response);
    }

    protected function updateEnv(array $data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        return file_put_contents($envFile, $envContent);
    }

    // protected function updateDebugbarConfig(array $config)
    // {
    //     $configFile = config_path('debugbar.php');

    //     // Ambil konfigurasi saat ini untuk menjaga widget default tetap tidak berubah
    //     $existingConfig = $this->getDebugbarConfig();

    //     // Hanya update widget yang di-request dan ada di form
    //     foreach ($existingConfig['widgets'] as $key => $value) {
    //         if (array_key_exists($key, $config['widgets'])) {
    //             $existingConfig['widgets'][$key] = $config['widgets'][$key];
    //         }
    //     }

    //     $content = "<?php\n\nreturn [\n";

    //     if (isset($existingConfig['widgets']) && is_array($existingConfig['widgets'])) {
    //         $content .= "    'widgets' => [\n";
    //         foreach ($existingConfig['widgets'] as $key => $value) {
    //             $content .= "        '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
    //         }
    //         $content .= "    ],\n";
    //     }

    //     $content .= "];\n";

    //     app('config')->set('debugbar.widgets', $existingConfig['widgets']);
    //     return file_put_contents($configFile, $content);
    // }

    // protected function getDebugbarConfig()
    // {
    //     $configFile = config_path('debugbar.php');
    //     return require $configFile;
    // }
}
