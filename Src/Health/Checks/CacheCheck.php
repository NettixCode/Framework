<?php

namespace Nettixcode\Framework\Health\Checks;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Nettixcode\Framework\Health\Check;
use Nettixcode\Framework\Health\Result;

class CacheCheck extends Check
{
    protected ?string $driver = null;

    public function driver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    public function run(): Result
    {
        $driver = $this->driver ?? $this->defaultDriver();
        $result = Result::make()->meta(['driver' => $driver]);

        try {
            return $this->canWriteValuesToCache($driver)
                ? $result->ok('Cache driver is working.')
                : $result->failed('Could not set or retrieve an application cache value.');
        } catch (Exception $exception) {
            return $result->failed("An exception occurred with the application cache: `{$exception->getMessage()}`");
        }
    }

    protected function defaultDriver(): ?string
    {
        return config('cache.default', 'file');
    }

    protected function canWriteValuesToCache(?string $driver): bool
    {
        $expectedValue = Str::random(5);
        $cacheName = "health-check:{$expectedValue}";

        Cache::driver($driver)->put($cacheName, $expectedValue, 10);
        $actualValue = Cache::driver($driver)->get($cacheName);
        Cache::driver($driver)->forget($cacheName);

        return $actualValue === $expectedValue;
    }
}
