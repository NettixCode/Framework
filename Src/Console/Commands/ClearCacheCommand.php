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
             ->setDescription('Clear all cache files and remove empty directories in cache/data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $frameworkDir = Config::get('app.paths.storage_path') . '/framework';
        $cacheDataDir = $frameworkDir . '/cache/data';
        $filesystem = new Filesystem();

        if ($filesystem->exists($frameworkDir)) {
            // Bersihkan semua file di dalam framework
            $this->clearFilesRecursively($frameworkDir, $filesystem);
            // Hapus folder kosong hanya di dalam cache/data
            if ($filesystem->exists($cacheDataDir)) {
                $this->removeEmptyDirectories($cacheDataDir, $filesystem);
            }
            $output->writeln('<info>Cache cleared and empty directories removed successfully!</info>');
        } else {
            $output->writeln('<comment>Framework directory does not exist.</comment>');
        }

        return Command::SUCCESS;
    }

    protected function clearFilesRecursively($dir, Filesystem $filesystem)
    {
        foreach ($filesystem->allFiles($dir) as $file) {
            $filesystem->delete($file->getRealPath());
        }
    }

    protected function removeEmptyDirectories($dir, Filesystem $filesystem)
    {
        foreach ($filesystem->directories($dir) as $subDir) {
            $this->removeEmptyDirectories($subDir, $filesystem);

            // Cek jika directory kosong setelah proses rekursif
            if (count($filesystem->allFiles($subDir)) === 0 && count($filesystem->directories($subDir)) === 0) {
                $filesystem->deleteDirectory($subDir);
            }
        }
    }
}
