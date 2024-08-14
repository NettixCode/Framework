<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddMigrationCommand extends Command
{
    protected static $defaultName = 'add:migration';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a new migration file to modify an existing table.')
             ->addArgument('table', InputArgument::REQUIRED, 'The name of the table.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table     = $input->getArgument('table');
        $className = 'Modify' . ucfirst($table) . 'Table';
        $filename  = date('Y_m_d_His') . '_modify_' . strtolower($table) . '_table.php';
        $path      = Config::get('app.paths.migrations') . '/' . $filename;

        $stubPath         = realpath(__DIR__ . '/stubs/add_column.stub');
        $stubContent      = file_get_contents($stubPath);
        $migrationContent = str_replace(['{{className}}', '{{tableName}}'], [$className, $table], $stubContent);

        file_put_contents($path, $migrationContent);

        $output->writeln("Migration created: {$filename}");

        return Command::SUCCESS;
    }
}
