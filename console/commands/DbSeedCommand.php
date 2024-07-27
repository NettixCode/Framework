<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Foundation\Providers\DatabaseServiceProvider;
use Nettixcode\Framework\Libraries\ConfigManager as Config;
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

class DbSeedCommand extends Command
{
    protected static $defaultName = 'db:seed';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Seed the database with records.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize Capsule
        $capsule = new Capsule();
        $capsule->addConnection(Config::load('database', 'connections')[Config::load('database', 'default')]);
        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Initialize Container and Facades
        $container = new Container();
        Facade::setFacadeApplication($container);

        // Bind Capsule to container
        $container->instance('capsule', $capsule);
        $container->instance('db', $capsule);

        // Register service provider
        $provider = new DatabaseServiceProvider($container);
        $provider->register();

        // Check and create seeders table if not exists
        if (!Schema::hasTable('seeders')) {
            Schema::create('seeders', function (Blueprint $table) {
                $table->id();
                $table->string('seeder');
                $table->timestamps();
            });
            $output->writeln('Created table: seeders');
        }

        // Find all seeder files
        $seedersPath = Config::load('app', 'paths.seeders');
        $finder      = new Finder();
        $finder->files()->in($seedersPath)->name('*.php');

        // Run all seeders
        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath());
            require_once $file->getRealPath();
            if (class_exists($className)) {
                // Check if seeder has already been run
                if (DB::table('seeders')->where('seeder', $className)->exists()) {
                    $output->writeln("Seeder $className already run, skipping...");
                    continue;
                }

                // Run seeder
                $seeder = new $className();
                $seeder->run();
                $output->writeln("Seeded: $className");

                // Record seeder as run
                DB::table('seeders')->insert([
                    'seeder'     => $className,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $output->writeln("Class $className does not exist.");
            }
        }

        $output->writeln('Database seeding completed.');

        return Command::SUCCESS;
    }

    private function getClassNameFromFile($filePath)
    {
        $content = file_get_contents($filePath);
        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
            return 'Database\\Seeders\\' . $matches[1];
        }

        return null;
    }
}
