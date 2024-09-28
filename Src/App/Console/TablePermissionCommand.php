<?php

namespace Nettixcode\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'table:permission')]
class TablePermissionCommand extends Command
{
    protected $signature = 'table:permission';
    protected $description = 'Check, create, and cleanup permissions for all tables.';

    // Daftar tabel yang di-exclude
    protected $excludedTables;

    public function handle(): int
    {
        $this->excludedTables = config('roper.exclude_table');
        $tables = DB::select('SHOW TABLES');
        $tables = array_map(fn($table) => array_values((array) $table)[0], $tables);

        // Check and create permissions for each table
        foreach ($tables as $tableName) {
            if (!in_array($tableName, $this->excludedTables)) {
                $this->checkAndCreatePermissions($tableName);
            }
        }

        // Cleanup permissions not linked to any existing table
        $this->cleanupUnusedPermissions($tables);

        $this->info('Permissions checked, created, and cleaned up successfully.');

        return Command::SUCCESS;
    }

    private function checkAndCreatePermissions($tableName)
    {
        $permissions = config('roper.default_permission');

        foreach ($permissions as $permission) {
            $permissionName = "$permission-$tableName";
            $exists = DB::table('permissions')->where('name', $permissionName)->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name'        => $permissionName,
                    'table_name'  => $tableName,
                    'description' => ucfirst($permission) . " $tableName permission",
                ]);
                $this->info("Permission created: $permissionName");
            }
            // Always assign permissions to roles
            $this->assignPermissionsToRoles($permissionName, $tableName);
        }
    }

    private function assignPermissionsToRoles($permissionName, $tableName)
    {
        $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

        // Get admin role id
        $adminRoleId = DB::table('roles')->where('name', config('roper.administrator.name'))->value('id');

        // Assign all permissions to admin
        DB::table('role_permissions')->updateOrInsert([
            'role_id'       => $adminRoleId,
            'permission_id' => $permissionId,
        ]);

        // Get all roles except admin
        $otherRoles = DB::table('roles')->where('name', '!=', config('roper.administrator.name'))->get();

        // Assign 'read' and 'update' permissions to every role except admin
        if (in_array($permissionName, ["read-$tableName", "update-$tableName"])) {
            foreach ($otherRoles as $role) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id'       => $role->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    private function cleanupUnusedPermissions($tables)
    {
        $existingTables = array_map(fn($table) => array_values((array) $table)[0], $tables);

        $permissions = DB::table('permissions')->get();

        foreach ($permissions as $permission) {
            if (!in_array($permission->table_name, $existingTables) ||
                in_array($permission->table_name, $this->excludedTables)) {

                DB::table('permissions')->where('id', $permission->id)->delete();
                DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                $this->info("Deleted permission: $permission->name linked to table: $permission->table_name");
            }
        }
    }
}
