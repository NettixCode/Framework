<?php

namespace Nettixcode\Framework\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nettixcode\Framework\Facades\Config;

class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a new model file.')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the model.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name      = $input->getArgument('name');
        $className = ucfirst($name);
        $filename  = $className . '.php';
        $path      = Config::get('app.paths.models') . '/' . $filename;

        if (file_exists($path)) {
            $output->writeln("Model already exists: {$filename}");

            return Command::SUCCESS;
        }

        $stubPath     = realpath(__DIR__ . '/stubs/model.stub');
        $stubContent  = file_get_contents($stubPath);
        $modelContent = str_replace('{{className}}', $className, $stubContent);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $modelContent);

        $output->writeln("Model created: {$filename}");

        return Command::SUCCESS;
    }
}
