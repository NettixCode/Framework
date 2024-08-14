<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TablePermissionCommand extends Command
{
    protected static $defaultName = 'table:permission';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Check, create, and cleanup permissions for all tables.');
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

        // Get list of all tables
        $tables         = $capsule->getConnection()->select('SHOW TABLES');
        $excludedTables = ['migrations', 'permissions', 'roles', 'role_permissions', 'seeders', 'user_roles'];
        $tableKey       = 'Tables_in_' . Config::get('database.connections')[Config::get('database.default')]['database'];
        $existingTables = array_map(function ($table) use ($tableKey) {
            return $table->$tableKey;
        }, $tables);

        // Check and create permissions for each table
        foreach ($existingTables as $tableName) {
            if (!in_array($tableName, $excludedTables)) {
                $this->checkAndCreatePermissions($tableName, $output);
            }
        }

        // Cleanup permissions not linked to any existing table
        $this->cleanupUnusedPermissions($existingTables, $output);

        $output->writeln('Permissions checked, created, and cleaned up successfully.');

        return Command::SUCCESS;
    }

    private function checkAndCreatePermissions($tableName, OutputInterface $output)
    {
        $permissions = ['create', 'read', 'update', 'delete'];

        foreach ($permissions as $permission) {
            $permissionName = "$permission-$tableName";
            $exists         = DB::table('permissions')->where('name', $permissionName)->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name'        => $permissionName,
                    'table_name'  => $tableName,
                    'description' => ucfirst($permission) . " $tableName permission",
                ]);

                $output->writeln("Permission created: $permissionName");
                $this->assignPermissionsToRoles($permissionName, $tableName);
            }
        }
    }

    private function assignPermissionsToRoles($permissionName, $tableName)
    {
        $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

        // Get admin role id
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        // Assign all permissions to admin
        DB::table('role_permissions')->insert([
            'role_id'       => $adminRoleId,
            'permission_id' => $permissionId,
        ]);

        // Get user role id
        $userRoleId = DB::table('roles')->where('name', 'user')->value('id');

        // Assign read and update permissions to user if applicable
        if (in_array($permissionName, ["read-$tableName", "update-$tableName"])) {
            DB::table('role_permissions')->insert([
                'role_id'       => $userRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    private function cleanupUnusedPermissions($existingTables, OutputInterface $output)
    {
        // Get permissions and delete those that are not linked to any existing table
        $permissions = DB::table('permissions')->get();
        foreach ($permissions as $permission) {
            if (!in_array($permission->table_name, $existingTables)) {
                DB::table('permissions')->where('id', $permission->id)->delete();
                DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                $output->writeln("Deleted permission: $permission->name linked to table: $permission->table_name");
            }
        }
    }
}
