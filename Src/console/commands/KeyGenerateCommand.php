<?php

namespace Nettixcode\Framework\Console\Commands;

use Nettixcode\Framework\Facades\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyGenerateCommand extends Command
{
    protected static $defaultName = 'key:generate';

    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Generate and set the application APP_KEY');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Path to the key generation flag file
        $flagFile = Config::get('app.files.keygenerateflag');

        // Debugging untuk memastikan path tidak kosong
        if (empty($flagFile)) {
            $output->writeln('<error>Key generate flag path is empty</error>');
            return Command::FAILURE;
        }
        // Debugging untuk memastikan bahwa path file adalah benar
        $output->writeln('<info>Key generate flag path: ' . $flagFile . '</info>');
        // Check if the key generation flag file exists
        if (file_exists($flagFile)) {
            $output->writeln('<info>Application key is already set. Skipping key generation.</info>');

            return Command::SUCCESS;
        }

        $key = 'base64:' . base64_encode(random_bytes(32));

        $envPath = Config::get('app.paths.base_path') . '/.env';
        if (!file_exists($envPath)) {
            $output->writeln('<error>.env file not found</error>');

            return Command::FAILURE;
        }

        $envContent = file_get_contents($envPath);

        if (preg_match('/^APP_KEY=/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $envContent);
        } else {
            $envContent .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envPath, $envContent);

        // Create the key generation flag file
        file_put_contents($flagFile, 'Application key generated');

        $output->writeln("<info>Application key [{$key}] set successfully.</info>");

        return Command::SUCCESS;
    }
}
