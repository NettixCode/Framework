<?php

namespace Nettixcode\Manager;

class ControllerAliasManager
{
    public static function generate()
    {
        $controllerDir   = base_path('app/Http/Controllers');
        $aliasConfigFile = storage_path('framework/cache/data/controlleralias.php');

        $namespace = '\\App\\Http\\Controllers\\';

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllerDir));
        $controllers = [];

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $alias = $file->getBasename('.php');
                $class = $namespace . $alias;
                $controllers[$alias] = $class;
            }
        }

        // // Load existing aliases (if file exists)
        // $existingAliases = file_exists($aliasConfigFile) ? include $aliasConfigFile : [];

        // // Filter out any non-string values in the existing aliases
        // $existingAliases = array_filter($existingAliases, fn($item) => is_string($item));

        // // Merge new aliases with existing aliases
        // $updatedAliases = array_merge($existingAliases, $controllers);

        // Create the configuration content without 'controller' group
        $configContent = "<?php\n\nreturn [\n";
        foreach ($controllers as $alias => $class) {
            $configContent .= "    '$alias' => $class::class,\n";
        }
        $configContent .= "];\n";

        // Write to file
        file_put_contents($aliasConfigFile, $configContent);
    }
}
