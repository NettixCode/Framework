<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewClearCommand extends Command
{
    protected static $defaultName = 'view:clear';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Clear View Cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = Config::get('app.paths.storage_path') . '/framework/views';
        foreach (glob($cacheDir . '/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $output->writeln('<info>View Cache cleared successfully!</info>');
        return Command::SUCCESS;
    }
}
