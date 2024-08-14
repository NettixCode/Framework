<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;

class ConfigCacheCommand extends Command
{
    protected static $defaultName = 'config:cache';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a cache file for faster configuration loading.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->clearConfigCache($output);
        $output->writeln('Cache clearing process completed.');
    
        // Get the ConfigManager instance
        $configManager = app('config');
        $output->writeln('ConfigManager instance obtained.');
    
        // Create the config cache
        $configManager->createConfigCache();
        $output->writeln('Cache creation process initiated.');
    
        $output->writeln('<info>Configuration cache created successfully!</info>');
    
        return Command::SUCCESS;
        }

    protected function clearConfigCache(OutputInterface $output)
    {
        // Define the path to the config cache file
        $configCachePath = app()->getCachedConfigPath();

        $filesystem = new Filesystem();

        if ($filesystem->exists($configCachePath)) {
            $filesystem->delete($configCachePath);
            $output->writeln('<info>Existing configuration cache cleared!</info>');
        } else {
            $output->writeln('<comment>No existing configuration cache found.</comment>');
        }
    }
}
