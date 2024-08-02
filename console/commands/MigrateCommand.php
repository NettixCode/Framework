<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Foundation\Providers\DatabaseServiceProvider;
use Nettixcode\Framework\Facades\Config;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Run the database migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize Capsule
        $capsule = new Capsule();
        // $config = require __DIR__ . '/../../../config/database.php';
        $capsule->addConnection(Config::get('database.connections')[Config::get('database.default')]);
        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Initialize Container and Facades
        $container = new Container();
        Facade::setFacadeApplication($container);

        // Bind Capsule to container
        $container->instance('capsule', $capsule);

        // Register service provider
        $provider = new DatabaseServiceProvider($container);
        $provider->register();

        // Setup schema facade
        Schema::setFacadeApplication($container);

        // Create migrations table if not exists
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });
        }

        // Find all migration files
        $migrationsPath = Config::get('app.paths.migrations');
        $finder         = new Finder();
        $finder->files()->in($migrationsPath)->name('*.php');

        // Get already run migrations
        $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();
        $batch         = DB::table('migrations')->max('batch') + 1;

        // Run migrations that haven't been run
        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath());
            if (!in_array($className, $ranMigrations)) {
                require $file->getRealPath();
                if (class_exists($className)) {
                    $migration = new $className();
                    $migration->up();

                    // Record migration
                    DB::table('migrations')->insert([
                        'migration'  => $className,
                        'batch'      => $batch,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    $output->writeln("Migrated: $className");

                    // Check if the migration creates a table and add permissions
                    if (method_exists($migration, 'getTableName')) {
                        $tableName = $migration->getTableName();
                        $this->createPermissions($tableName);
                        $output->writeln("Permissions created for table: $tableName");

                        // Assign permissions to roles
                        $this->assignPermissionsToRoles($tableName);
                        $output->writeln("Permissions assigned to roles for table: $tableName");
                    }
                } else {
                    $output->writeln("Class $className does not exist.");
                }
            } else {
                $output->writeln("Already migrated: $className");
            }
        }

        $output->writeln('Migrations run successfully.');

        return Command::SUCCESS;
    }

    private function getClassNameFromFile($filePath)
    {
        $content = file_get_contents($filePath);
        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function createPermissions($tableName)
    {
        $permissions = ['create', 'read', 'update', 'delete'];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name'        => "$permission-$tableName",
                'table_name'  => $tableName,
                'description' => ucfirst($permission) . " $tableName permission",
            ]);
        }
    }

    private function assignPermissionsToRoles($tableName)
    {
        // Get permission ids
        $permissions = DB::table('permissions')->where('table_name', $tableName)->pluck('id', 'name')->toArray();

        // Get admin role id
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        // Assign all permissions to admin
        foreach ($permissions as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id'       => $adminRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        // Get user role id
        $userRoleId = DB::table('roles')->where('name', 'user')->value('id');

        // Assign read and update permissions to user
        foreach (['read', 'update'] as $permission) {
            $permissionId = $permissions["$permission-$tableName"];
            DB::table('role_permissions')->insert([
                'role_id'       => $userRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
