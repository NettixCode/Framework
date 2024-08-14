<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nettixcode\Framework\Facades\Route;
use Nettixcode\Framework\Facades\Config;

class RouteCacheCommand extends Command
{
    protected static $defaultName = 'route:cache';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Generate route cache file');
    }

    private static function generateRouteName($action)
    {
        if (is_array($action)) {
            return end($action);
        } elseif (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            return $method;
        }
        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Ambil semua route yang terdaftar
        $routes = Route::getRoutes();
        // Simpan route ke dalam file cache
        $filePath = Config::get('app.base_path') . '/bootstrap/cache/routes-v7.php';
        $directory = dirname($filePath);
    
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    
        $routeData = [];
    
        foreach ($routes as $route) {
            $name = $route->getName();
            
            // Jika nama rute null, generate nama menggunakan fungsi generateRouteName
            if (is_null($name)) {
                $name = self::generateRouteName($route->getAction('uses'));
            }
    
            $routeData[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'action' => $route->getActionName(),
                'name' => $name,
                'middleware' => $route->gatherMiddleware(),
            ];
        }
    
        file_put_contents($filePath, '<?php return ' . var_export($routeData, true) . ';');
    
        $output->writeln('<info>Route cache generated successfully.</info>');
    
        return Command::SUCCESS;
    }
}
