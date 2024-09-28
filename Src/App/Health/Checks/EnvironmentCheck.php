<?php

namespace Nettixcode\App\Health\Checks;

use Nettixcode\App\Health\Check;
use Nettixcode\App\Health\Result;

class EnvironmentCheck extends Check
{
    protected string $expectedEnvironment = 'production';

    public function expectEnvironment(string $expectedEnvironment): self
    {
        $this->expectedEnvironment = $expectedEnvironment;
        return $this;
    }

    public function run(): Result
    {
        $actualEnvironment = env('APP_ENV', 'unknown'); // Menggunakan env() untuk mendapatkan nilai dari .env

        $result = Result::make()
            ->meta([
                'actual' => $actualEnvironment,
                'expected' => $this->expectedEnvironment,
            ]);

        // Mengganti placeholder di pesan
        $message = $this->expectedEnvironment === $actualEnvironment
            ? 'Environment is correctly set to `:expected`'
            : 'The environment was expected to be `:expected`, but actually was `:actual`';

        $message = $this->replacePlaceholders($message, $result->getMeta());

        return $this->expectedEnvironment === $actualEnvironment
            ? $result->ok($message)
            : $result->failed($message);
    }

    private function replacePlaceholders(string $message, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $message = str_replace(':'.$key, $value, $message);
        }
        return $message;
    }
}
