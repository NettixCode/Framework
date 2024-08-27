<?php

namespace Nettixcode\Framework\Console;

use Symfony\Component\Console\Application;

class Kernel
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->addCommand(new $command);
        }
    }

    public function addCommand($command)
    {
        if (isset($this->app->consoleApplication)){
            $this->app->consoleApplication->add($command);
        }
    }
}
