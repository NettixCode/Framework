<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteClearCommand extends Command
{
    protected static $defaultName = 'route:clear';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Clear the route cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Path to the route cache file
        $filePath = app('cachedRoutesPath');

        if (file_exists($filePath)) {
            unlink($filePath);
            $output->writeln('<info>Route cache cleared successfully.</info>');
        } else {
            $output->writeln('<comment>No route cache file found.</comment>');
        }

        return Command::SUCCESS;
    }
}
