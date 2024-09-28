<?php

namespace Nettixcode\App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use \Nettixcode\App\Health\Checks\UsedDiskSpaceCheck;
use \Nettixcode\App\Health\Checks\EnvironmentCheck;
use \Nettixcode\App\Health\Checks\CacheCheck;
use \Nettixcode\App\Health\Checks\DebugModeCheck;
use \Nettixcode\App\Health\Checks\DatabaseCheck;

class SystemHealthController
{
    public function index()
    {
        $status = [
            'Debug Mode' => $this->checkDebugMode(),
            'Database' => $this->checkDatabaseConnection(),
            'Cache' => $this->checkCacheConnection(),
            'Disk Usage' => $this->checkDiskSpace(),
            'Environment' => $this->checkEnvironment(),
            'Login Time' => $this->checkLoginTime(),
        ];

        return view('admin.system-health', ['status' => $status]);
    }

    private function checkDebugMode(){
        $debugModeCheck = new DebugModeCheck();
        $debugModeCheck->expectedToBe(false);
        $result = $debugModeCheck->run();

        return [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ];
    }

    private function checkDatabaseConnection()
    {
        $check = new DatabaseCheck();
        $result = $check->connectionName(config('database.default'))->run();

        return [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ];
    }

    private function checkCacheConnection()
    {
        $cacheCheck = new CacheCheck();
        $result = $cacheCheck->run();

        return [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ];
    }

    private function checkDiskSpace()
    {
        $diskSpaceCheck = (new UsedDiskSpaceCheck())
            ->setFilesystemName('/')
            ->warnWhenUsedSpaceIsAbovePercentage(70)
            ->failWhenUsedSpaceIsAbovePercentage(90);

        $result = $diskSpaceCheck->run();

        return [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ];
    }

    private function checkEnvironment()
    {
        $environmentCheck = new EnvironmentCheck();
        $result = $environmentCheck
            ->expectEnvironment('production') // Ubah sesuai kebutuhan
            ->run();

        return [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ];
    }

    private function checkLoginTime()
    {
        $start = session()->has('SYSTEM_UP') ? session('SYSTEM_UP') : '0';
        $uptime = microtime(true) - $start;

        return [
            'status' => 'ok',
            'message' => gmdate('H:i:s', (int) $uptime),
        ];
    }
}
