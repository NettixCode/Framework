<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;

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
        $cacheDir = Config::get('app.paths.storage_path') . '/framework';
        $filesystem = new Filesystem();

        if ($filesystem->exists($cacheDir)) {
            $this->clearFilesRecursively($cacheDir, $filesystem);
            $output->writeln('<info>Cache cleared successfully!</info>');
        } else {
            $output->writeln('<comment>Cache directory does not exist.</comment>');
        }

        return Command::SUCCESS;
    }

    protected function clearFilesRecursively($dir, Filesystem $filesystem)
    {
        foreach ($filesystem->allFiles($dir) as $file) {
            $filesystem->delete($file->getRealPath());
        }
    }
}
