<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;

class ConfigClearCommand extends Command
{
    protected static $defaultName = 'config:clear';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Remove the configuration cache file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configCachePath = app()->getCachedConfigPath();
        $output->writeln("Trying to clear cache at: " . $configCachePath);

        $filesystem = new Filesystem();

        if ($filesystem->exists($configCachePath)) {
            $filesystem->delete($configCachePath);
            $output->writeln('<info>Existing configuration cache cleared!</info>');
        } else {
            $output->writeln('<comment>No existing configuration cache found.</comment>');
        }

        return Command::SUCCESS;
    }
}
