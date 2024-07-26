<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Libraries\ConfigManager as Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Create a new controller file.')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name      = $input->getArgument('name');
        $className = ucfirst($name) . 'Controller';
        $filename  = $className . '.php';
        $path      = Config::load('app', 'paths.controllers') . '/' . $filename;

        if (file_exists($path)) {
            $output->writeln("Controller already exists: {$filename}");

            return Command::SUCCESS;
        }

        $stubPath          = realpath(__DIR__ . '/stubs/controller.stub');
        $stubContent       = file_get_contents($stubPath);
        $controllerContent = str_replace('{{className}}', $className, $stubContent);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $controllerContent);

        $output->writeln("Controller created: {$filename}");

        return Command::SUCCESS;
    }
}
