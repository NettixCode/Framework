<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Providers\DatabaseServiceProvider;
use Nettixcode\Framework\Libraries\ConfigManager as Config;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class RollbackCommand extends Command
{
    protected static $defaultName = 'rollback';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Rollback the last database migration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize Capsule
        $capsule = new Capsule();
        // $config = require __DIR__ . '/../../../config/database.php';
        $capsule->addConnection(Config::load('database', 'connections')[Config::load('database', 'default')]);
        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Initialize Container and Facades
        $container = new Container();
        Facade::setFacadeApplication($container);

        // Bind Capsule and DB to container
        $container->instance('capsule', $capsule);
        $container->instance('db', $capsule);

        // Register service provider
        $provider = new DatabaseServiceProvider($container);
        $provider->register();

        // Setup schema facade
        Schema::setFacadeApplication($container);

        // Get the latest batch number
        $batch = DB::table('migrations')->max('batch');

        if ($batch === null) {
            $output->writeln('No migrations to rollback.');

            return Command::SUCCESS;
        }

        // Get migrations for the latest batch
        $migrations = DB::table('migrations')->where('batch', $batch)->orderBy('id', 'desc')->get();

        foreach ($migrations as $migration) {
            $className = $migration->migration;
            $filePath  = Config::load('app', 'paths.migrations') . '/' . $this->getFileNameFromClassName($className);

            if (file_exists($filePath)) {
                require_once $filePath;

                if (class_exists($className)) {
                    $instance = new $className();
                    if (method_exists($instance, 'getTableName')) {
                        $tableName = $instance->getTableName();
                        $this->deletePermissions($tableName, $output);
                    }

                    $instance->down();

                    DB::table('migrations')->where('id', $migration->id)->delete();

                    $output->writeln("Rolled back: $className");
                } else {
                    $output->writeln("Class $className does not exist.");
                }
            } else {
                $output->writeln("Migration file for class $className does not exist.");
            }
        }

        $output->writeln('Rollback completed.');

        return Command::SUCCESS;
    }

    private function getFileNameFromClassName($className)
    {
        $finder = new Finder();
        $finder->files()->in(Config::load('app', 'paths.migrations'))->name('*.php');

        foreach ($finder as $file) {
            $content = file_get_contents($file->getRealPath());
            if (strpos($content, "class $className") !== false) {
                return $file->getRelativePathname();
            }
        }

        return null;
    }

    private function deletePermissions($tableName, OutputInterface $output)
    {
        $permissions = DB::table('permissions')->where('table_name', $tableName)->get();
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
            $output->writeln("Deleted permission: $permission->name linked to table: $permission->table_name");
        }
    }
}
