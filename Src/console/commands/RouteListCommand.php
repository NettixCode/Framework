<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Route;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class RouteListCommand extends Command
{
    protected static $defaultName = 'route:list';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('List all registered routes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = Route::getRoutes();

        $table = new Table($output);
        $table->setHeaders(['Method', 'URI', 'Name', 'Action']);

        foreach ($routes as $route) {
            $table->addRow([
                implode('|', $route->methods()),
                $route->uri(),
                $route->getName(),
                $route->getActionName(),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
