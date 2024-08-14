<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a new migration file.')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name      = $input->getArgument('name');
        $className = 'Create' . ucfirst($name) . 'Table';
        $filename  = date('Y_m_d_His') . '_create_' . strtolower($name) . '_table.php';
        $path      = Config::get('app.paths.migrations') . '/' . $filename;

        if (file_exists($path)) {
            $output->writeln("Migration already exists: {$filename}");

            return Command::SUCCESS;
        }

        $stubPath         = realpath(__DIR__ . '/stubs/migration.stub');
        $stubContent      = file_get_contents($stubPath);
        $migrationContent = str_replace(['{{className}}', '{{tableName}}'], [$className, $name], $stubContent);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $migrationContent);

        $output->writeln("Migration created: {$filename}");

        return Command::SUCCESS;
    }
}
