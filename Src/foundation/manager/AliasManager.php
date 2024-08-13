<?php

namespace Nettixcode\Framework\Foundation\Manager;

use Nettixcode\Framework\Foundation\Manager\ConfigManager;

class AliasManager
{
    protected $config;

    public function __construct(){
        $this->config = new ConfigManager();
    }

    public static function generate()
    {
        $instance = new Static;
        $controllerDir   = $instance->config->get('app.paths.controllers');
        $aliasConfigFile = $instance->config->get('app.files.aliases');

        $namespace = '\\Application\\Http\\Controllers\\';

        // Membuat daftar file di direktori controller
        $files       = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));
        $controllers = [];

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath        = str_replace([$controllerDir . DIRECTORY_SEPARATOR, '.php'], '', $file->getRealPath());
                $alias               = basename($relativePath);
                $className           = $namespace . $alias;
                $controllers[$alias] = $className;
            }
        }

        // Muat alias yang sudah ada dari file konfigurasi
        $existingAliases = [];
        if (file_exists($aliasConfigFile)) {
            $existingAliases = include $aliasConfigFile;
        }

        // Gabungkan alias controller dengan alias yang sudah ada, tanpa duplikasi
        $updatedAliases = [
            'controller' => [],
        ];

        // Menambahkan alias controller yang baru
        foreach ($controllers as $alias => $class) {
            $updatedAliases['controller'][$alias] = $class;
        }

        // Menambahkan alias non-controller yang sudah ada
        foreach ($existingAliases as $group => $aliases) {
            foreach ($aliases as $alias => $class) {
                if ($group === 'controller') {
                    // Jika alias controller sudah tidak ada, jangan tambahkan kembali
                    if (!isset($controllers[$alias])) {
                        continue;
                    }
                }
                // Jika alias tidak ada di updatedAliases, tambahkan
                if (!isset($updatedAliases[$group][$alias])) {
                    $updatedAliases[$group][$alias] = $class;
                }
            }
        }

        // Buat konten file konfigurasi alias
        $configContent = "<?php\n\nreturn [\n";
        foreach ($updatedAliases as $group => $aliases) {
            $configContent .= "    '$group' => [\n";
            foreach ($aliases as $alias => $class) {
                $configContent .= "        '$alias' => $class::class,\n";
            }
            $configContent .= "    ],\n";
        }
        $configContent .= "];\n";

        // Tulis ke file konfigurasi alias
        file_put_contents($aliasConfigFile, $configContent);
    }
}
