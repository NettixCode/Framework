<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;

class RouteCacheCommand extends Command
{
    protected static $defaultName = 'route:cache';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Generate route cache file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting route cache generation...</info>');
    
        // Membersihkan cache route sebelumnya
        try {
            $this->clearRouteCache($output);
            $output->writeln('<info>Route cache cleared.</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error clearing route cache: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    
        // Mengambil route yang baru
        try {
            $routes = $this->getFreshApplicationRoutes($output);
            $output->writeln('<info>Routes retrieved: ' . count($routes) . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error retrieving routes: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    
        // Cek apakah ada route yang terdaftar
        if (count($routes) === 0) {
            $output->writeln('<error>Your application doesn\'t have any routes.</error>');
            return Command::FAILURE;
        }
    
        // Serialization untuk setiap route
        try {
            foreach ($routes as $route) {
                $route->prepareForSerialization();
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Error preparing routes for serialization: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    
        // Cache route ke file
        try {
            $filesystem = new Filesystem();
            $cacheFilePath = $this->getCachedRoutesPath();
            $cacheContent = $this->buildRouteCacheFile($routes);
            $filesystem->put($cacheFilePath, $cacheContent);
    
            if ($filesystem->exists($cacheFilePath)) {
                $output->writeln('<info>Routes cached successfully at ' . $cacheFilePath . '</info>');
            } else {
                $output->writeln('<error>Failed to cache routes.</error>');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Error during Filesystem operation: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
    
    protected function clearRouteCache(OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $cacheFile = $this->getCachedRoutesPath();
        if ($filesystem->exists($cacheFile)) {
            $filesystem->delete($cacheFile);
            $output->writeln('<info>Route cache cleared successfully.</info>');
        } else {
            $output->writeln('<info>No existing route cache to clear.</info>');
        }
    }
        
    protected function getFreshApplicationRoutes(OutputInterface $output)
    {
        $router = app('router');
        $routes = $router->getRoutes();
    
        if (empty($routes)) {
            $output->writeln("No routes found.");
        } else {
            $output->writeln(count($routes) . " routes found.");
        }
    
        return $routes;
    }
    
    protected function buildRouteCacheFile($routes)
    {
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
        $filesystem = new Filesystem();
        $stub = $filesystem->get(__DIR__ . '/stubs/routes.stub');
        
        return str_replace('{{routes}}', var_export($routes->compile(), true), $stub);
    }

    protected function getCachedRoutesPath()
    {
        return app('cachedRoutesPath');
    }
}
