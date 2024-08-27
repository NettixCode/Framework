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
        $aliasConfigFile = $instance->config->get('app.cache_paths.data') . '/controlleraliases.php';

        $namespace = '\\Application\\Http\\Controllers\\';

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

        $existingAliases = [];
        if (!is_null($aliasConfigFile) && file_exists($aliasConfigFile)) {
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
                    if (!isset($controllers[$alias])) {
                        continue;
                    }
                }
                if (!isset($updatedAliases[$group][$alias])) {
                    $updatedAliases[$group][$alias] = $class;
                }
            }
        }

        $configContent = "<?php\n\nreturn [\n";
        foreach ($updatedAliases as $group => $aliases) {
            $configContent .= "    '$group' => [\n";
            foreach ($aliases as $alias => $class) {
                $configContent .= "        '$alias' => $class::class,\n";
            }
            $configContent .= "    ],\n";
        }
        $configContent .= "];\n";

        file_put_contents($aliasConfigFile, $configContent);
    }
}
