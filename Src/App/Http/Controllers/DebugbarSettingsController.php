<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\facades\Auth;

class DebugbarSettingsController
{
    public function index()
    {
        $debugbarConfig = config('debugbar');

        return Auth::user()->isAdmin() ?
        view('admin.debugbar-settings', compact('debugbarConfig')) :
        abort(403);
    }

    public function update(Request $request)
    {
        // Path ke file config
        $configPath = config_path('debugbar.php');

        // Ambil konfigurasi saat ini
        $existingConfig = include $configPath;

        // Update nilai 'enabled'
        // if ($request->has('enabled')) {
        //     $enabledValue = $request->input('enabled') === 'true';
        //     $existingConfig['enabled'] = $enabledValue;
        // }

        // Update bagian 'collectors'
        if ($request->has('collectors')) {
            $collectorsInput = $request->input('collectors');

            // Pastikan hanya collectors yang valid yang diupdate
            foreach ($collectorsInput as $key => $value) {
                if (array_key_exists($key, $existingConfig['collectors'])) {
                    $existingConfig['collectors'][$key] = ($value === 'true');
                }
            }
        }

        // Baca konten file konfigurasi
        $configFileContent = file_get_contents($configPath);

        // Update bagian 'enabled' di dalam konten file
        // $updatedConfigContent = preg_replace(
        //     '/\'enabled\' => (true|false),/',
        //     '\'enabled\' => ' . var_export($existingConfig['enabled'], true) . ',',
        //     $configFileContent
        // );

        // Update bagian 'collectors' di dalam konten file
        $updatedConfigContent = preg_replace(
            '/\'collectors\' => \[(.*?)\],/s',
            '\'collectors\' => [' . "\n" . '        ' . implode(",\n        ", array_map(function($k, $v) {
                return "'$k' "."\t\t"."=> " . ($v ? 'true' : 'false');
            }, array_keys($existingConfig['collectors']), $existingConfig['collectors'])) . "\n    ],",
            $configFileContent
        );

        // Simpan perubahan ke file konfigurasi
        $configUpdated = file_put_contents($configPath, $updatedConfigContent);

        // Siapkan respons
        $response = ['report' => 'error', 'message' => 'Failed to update settings.'];
        if ($configUpdated) {
            $response = ['report' => 'success', 'message' => 'Settings have been updated!'];
        }

        return response()->json($response);
    }
    }
