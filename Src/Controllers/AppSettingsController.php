<?php

namespace Nettixcode\Framework\Controllers;

use Nettixcode\Framework\Http\Request;
use Nettixcode\Framework\Facades\NxEngine;
use Nettixcode\Framework\Facades\User;

class AppSettingsController
{
    public function index()
    {
        debug_send('messages', 'viewing setting pages.');
        return User::has('role', 'admin') ?
            NxEngine::view('admin.app-settings') :
            NxEngine::view('errors.error-403');
    }

    public function update(Request $request)
    {
        $keyChanged = false;
        $currentAppKey = env('APP_KEY');

        // Update .env file
        $this->updateEnv([
            'APP_NAME' => $request->input('APP_NAME'),
            'APP_ENV' => $request->input('APP_ENV'),
            'APP_KEY' => $request->input('APP_KEY'),
            'APP_URL' => $request->input('APP_URL'),
            'APP_LOGO' => $request->input('APP_LOGO'),
            'APP_TIMEZONE' => $request->input('APP_TIMEZONE'),
            'APP_DEBUG' => $request->has('APP_DEBUG') ? 'true' : 'false',
        ]);

        // Cek apakah APP_KEY berubah
        if ($currentAppKey !== $request->input('APP_KEY')) {
            $keyChanged = true;
        }

        // Update debugbar.php config
        $debugbarConfig = $this->getDebugbarConfig();
        $debugbarConfig['widgets'] = [
            'messages' => $request->has('widgets.messages'),
            'request' => $request->has('widgets.request'),
            'time' => $request->has('widgets.time'),
            'exceptions' => $request->has('widgets.exceptions'),
            'httpRequest' => $request->has('widgets.httpRequest'),
            'route' => $request->has('widgets.route'),
            'query' => $request->has('widgets.query'),
            'session' => $request->has('widgets.session'),
            'config' => $request->has('widgets.config'),
        ];

        $configUpdated = $this->updateDebugbarConfig($debugbarConfig);

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

        file_put_contents($envFile, $envContent);
    }

    protected function updateDebugbarConfig(array $config)
    {
        $configFile = config_path('debugbar.php');
    
        // Ambil konfigurasi saat ini untuk menjaga widget default tetap tidak berubah
        $existingConfig = $this->getDebugbarConfig();
    
        // Hanya update widget yang di-request dan ada di form
        foreach ($existingConfig['widgets'] as $key => $value) {
            if (array_key_exists($key, $config['widgets'])) {
                $existingConfig['widgets'][$key] = $config['widgets'][$key];
            }
        }
    
        $content = "<?php\n\nreturn [\n";
    
        if (isset($existingConfig['widgets']) && is_array($existingConfig['widgets'])) {
            $content .= "    'widgets' => [\n";
            foreach ($existingConfig['widgets'] as $key => $value) {
                $content .= "        '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
            }
            $content .= "    ],\n";
        }
    
        $content .= "];\n";
    
        app('config')->set('debugbar.widgets', $existingConfig['widgets']);
        return file_put_contents($configFile, $content);
    }
    
    protected function getDebugbarConfig()
    {
        $configFile = config_path('debugbar.php');
        return require $configFile;
    }
}
