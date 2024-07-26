<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Libraries\ConfigManager as Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Clear Cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = Config::load('app', 'paths.storage_path') . '/cache';
        foreach (glob($cacheDir . '/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return Command::SUCCESS;
    }
}
