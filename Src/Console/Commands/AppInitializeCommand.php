<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppInitializeCommand extends Command
{
    protected static $defaultName = 'app:initialize';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Initialize the application with default database and tables.')
             ->addArgument('username_admin', InputArgument::REQUIRED, 'The username for the default admin user.')
             ->addArgument('password_admin', InputArgument::REQUIRED, 'The password for the default admin user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Path to the initialization flag file
        $flagFile = Config::get('app.files.initializeflag');

        // Check if the initialization flag file exists
        if (file_exists($flagFile)) {
            $output->writeln('Application is already initialized. Skipping initialization.');

            return Command::SUCCESS;
        }

        $usernameAdmin = $input->getArgument('username_admin');
        $password      = $input->getArgument('password_admin');

        $capsule          = new Capsule();
        $connectionConfig = [
            'driver'    => env('DB_CONNECTION'),
            'host'      => env('DB_HOST'),
            'database'  => null, // Set to null to connect without specifying a database
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => env('DB_CHARSET'),
            'collation' => env('DB_COLLATION'),
            'prefix'    => env('DB_PREFIX'),
        ];

        // Create connection without database
        $capsule->addConnection($connectionConfig, 'initial');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $pdo = $capsule->getConnection('initial')->getPdo();

        // Create database if not exists
        $dbName    = env('DB_DATABASE');
        $charset   = env('DB_CHARSET');
        $collation = env('DB_COLLATION');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE $collation");

        // Connect to the newly created database
        $connectionConfig['database'] = $dbName;
        $capsule->addConnection($connectionConfig);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $schema = $capsule->schema();

        // Create tables if not exists
        if (!$schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->increments('id');
                $table->string('username')->unique();
                $table->string('password');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone')->unique()->nullable();
                $table->string('email')->unique()->nullable();
                $table->string('status');
                $table->string('profile_picture')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('roles')) {
            $schema->create('roles', function ($table) {
                $table->increments('id');
                $table->string('name')->unique()->nullable();
                $table->string('description')->nullable();
            });
        }

        if (!$schema->hasTable('permissions')) {
            $schema->create('permissions', function ($table) {
                $table->increments('id');
                $table->string('name')->unique()->nullable();
                $table->string('table_name')->nullable();
                $table->string('description')->nullable();
            });
        }

        if (!$schema->hasTable('user_roles')) {
            $schema->create('user_roles', function ($table) {
                $table->integer('user_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->primary(['user_id', 'role_id']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            });
        }

        if (!$schema->hasTable('role_permissions')) {
            $schema->create('role_permissions', function ($table) {
                $table->integer('role_id')->unsigned();
                $table->integer('permission_id')->unsigned();
                $table->primary(['role_id', 'permission_id']);
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            });
        }

        // Insert default data if not exists
        if (!$capsule->table('roles')->where('name', 'admin')->exists()) {
            $adminRoleId = $capsule->table('roles')->insertGetId([
                'name'        => 'admin',
                'description' => 'Administrator',
            ]);
        } else {
            $adminRoleId = $capsule->table('roles')->where('name', 'admin')->value('id');
        }

        if (!$capsule->table('roles')->where('name', 'user')->exists()) {
            $userRoleId = $capsule->table('roles')->insertGetId([
                'name'        => 'user',
                'description' => 'User',
            ]);
        } else {
            $userRoleId = $capsule->table('roles')->where('name', 'user')->value('id');
        }

        if (!$capsule->table('permissions')->where('name', 'create-users')->exists()) {
            $createUserPermissionId = $capsule->table('permissions')->insertGetId([
                'name'        => 'create-users',
                'table_name'  => 'users',
                'description' => 'Create user permission',
            ]);
        } else {
            $createUserPermissionId = $capsule->table('permissions')->where('name', 'create-users')->value('id');
        }

        if (!$capsule->table('permissions')->where('name', 'read-users')->exists()) {
            $readUserPermissionId = $capsule->table('permissions')->insertGetId([
                'name'        => 'read-users',
                'table_name'  => 'users',
                'description' => 'Read user permission',
            ]);
        } else {
            $readUserPermissionId = $capsule->table('permissions')->where('name', 'read-users')->value('id');
        }

        if (!$capsule->table('permissions')->where('name', 'update-users')->exists()) {
            $updateUserPermissionId = $capsule->table('permissions')->insertGetId([
                'name'        => 'update-users',
                'table_name'  => 'users',
                'description' => 'Update user permission',
            ]);
        } else {
            $updateUserPermissionId = $capsule->table('permissions')->where('name', 'update-users')->value('id');
        }

        if (!$capsule->table('permissions')->where('name', 'delete-users')->exists()) {
            $deleteUserPermissionId = $capsule->table('permissions')->insertGetId([
                'name'        => 'delete-users',
                'table_name'  => 'users',
                'description' => 'Delete permission permission',
            ]);
        } else {
            $deleteUserPermissionId = $capsule->table('permissions')->where('name', 'delete-users')->value('id');
        }

        // Assign all permissions to admin role if not assigned yet
        $rolePermissions = $capsule->table('role_permissions')
            ->where('role_id', $adminRoleId)
            ->pluck('permission_id')
            ->toArray();

        $permissionsToAssign = array_diff([$createUserPermissionId, $readUserPermissionId, $updateUserPermissionId, $deleteUserPermissionId], $rolePermissions);

        foreach ($permissionsToAssign as $permissionId) {
            $capsule->table('role_permissions')->insert([
                'role_id'       => $adminRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        $userPermissions = $capsule->table('role_permissions')
        ->where('role_id', $userRoleId)
        ->pluck('permission_id')
        ->toArray();

        $permissionsToAssignForUser = array_diff([$readUserPermissionId, $updateUserPermissionId], $userPermissions);

        foreach ($permissionsToAssignForUser as $permissionId) {
            $capsule->table('role_permissions')->insert([
                'role_id'       => $userRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        // Create default admin user if not exists
        if (!$capsule->table('users')->where('username', $usernameAdmin)->exists()) {
            $defaultAdminPassword = password_hash($password, PASSWORD_BCRYPT);

            $adminUserId = $capsule->table('users')->insertGetId([
                'username'          => $usernameAdmin,
                'password'          => $defaultAdminPassword,
                'status'            => 1,
                'profile_picture'   => 'img/users/default.webp',
            ]);

            // Assign admin role to default admin user
            $capsule->table('user_roles')->insert([
                'user_id' => $adminUserId,
                'role_id' => $adminRoleId,
            ]);
        }

        // Create the initialization flag file
        file_put_contents($flagFile, 'Application initialized');

        $output->writeln('Application initialized successfully.');

        return Command::SUCCESS;
    }
}
