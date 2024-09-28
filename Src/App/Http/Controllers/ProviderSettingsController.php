<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Nettixcode\Facades\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\App;

class ProviderSettingsController
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function index()
    {
        $providersConfig = $this->getProvidersConfig();

        $manifest = App::make(PackageManifest::class);
        $manifest->build();
        $runningProviders = collect($manifest->manifest)->keys()
            ->map(fn($provider) => ['provider' => $provider . ' ( Auto Discover Package )'])
            ->toArray();

        $providersConfig['package_providers'] = array_merge(
            $providersConfig['package_providers'] ?? [],
            $runningProviders
        );

        $providers = $providersConfig;

        return Auth::user()->isAdmin() ?
            view('admin.provider-settings', compact('providers')) :
            abort(403);
    }

    public function getProvidersConfig()
    {
        $filePath = storage_path('app/private/nxcode-provider.json');

        if ($this->files->exists($filePath)) {
            $json = $this->files->get($filePath);
            return json_decode($json, true);
        }

        return []; // Mengembalikan array kosong jika file tidak ditemukan
    }

    public function update(Request $request)
    {
        $config = [
            'package_providers' => [],
            'application_providers' => [],
            'added_providers' => []
        ];

        // Update 'package_providers'
        foreach ($request->input('package_providers', []) as $providerName => $isEnabled) {
            $config['package_providers'][] = [
                'provider' => $providerName,
                'enabled' => $isEnabled === 'true' ? true : false
            ];
        }

        // Update 'application_providers'
        foreach ($request->input('application_providers', []) as $providerName => $isEnabled) {
            $config['application_providers'][] = [
                'provider' => $providerName,
                'enabled' => $isEnabled === 'true' ? true : false
            ];
        }

        // Update 'added_providers'
        foreach ($request->input('added_providers', []) as $providerName => $isEnabled) {
            $config['added_providers'][] = [
                'provider' => $providerName,
                'enabled' => $isEnabled === 'true' ? true : false
            ];
        }

        $filePath = storage_path('app/private/nxcode-provider.json');

        // Menyimpan konfigurasi yang diperbarui ke file JSON menggunakan Filesystem
        if ($this->files->exists($filePath)) {
            $this->files->put($filePath, json_encode($config, JSON_PRETTY_PRINT));
            $response = ['report' => 'success', 'message' => 'Settings have been updated!'];
        } else {
            $response = ['report' => 'error', 'message' => 'File not found.'];
        }

        return response()->json($response);
    }
}
