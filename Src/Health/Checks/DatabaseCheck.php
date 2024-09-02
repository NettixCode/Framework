<?php

namespace Nettixcode\Framework\Health\Checks;

use Exception;
use Illuminate\Support\Facades\DB;
use Nettixcode\Framework\Health\Check;
use Nettixcode\Framework\Health\Result;

class DatabaseCheck extends Check
{
    protected ?string $connectionName = null;

    public function connectionName(string $connectionName): self
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    public function run(): Result
    {
        // Gunakan nama koneksi yang diatur atau default
        $connectionName = $this->connectionName ?? $this->getDefaultConnectionName();
        $result = Result::make()->meta(['connection_name' => $connectionName]);

        try {
            DB::connection($connectionName)->getPdo();
            return $result->ok('Database connection is up.');
        } catch (Exception $exception) {
            return $result->failed("Could not connect to the database: `{$exception->getMessage()}`");
        }
    }

    protected function getDefaultConnectionName(): string
    {
        // Mengembalikan nama koneksi default
        return config('database.default');
    }
}
