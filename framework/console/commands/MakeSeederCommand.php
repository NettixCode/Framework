<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Libraries\ConfigManager as Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeSeederCommand extends Command
{
    protected static $defaultName = 'make:seeder';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a new seeder file.')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the seeder.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name      = $input->getArgument('name');
        $className = ucfirst($name) . 'Seeder';
        $filename  = $className . '.php';
        $path      = Config::load('app', 'paths.seeders') . '/' . $filename;

        if (file_exists($path)) {
            $output->writeln("Seeder already exists: {$filename}");

            return Command::SUCCESS;
        }

        $stubPath      = realpath(__DIR__ . '/stubs/seeder.stub');
        $stubContent   = file_get_contents($stubPath);
        $seederContent = str_replace('{{className}}', $className, $stubContent);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $seederContent);

        $output->writeln("Seeder created: {$filename}");

        return Command::SUCCESS;
    }
}
