<?php

namespace Nettixcode\App\Health\Checks;

use Nettixcode\App\Health\Check;
use Nettixcode\App\Health\Result;
use Symfony\Component\Process\Process;

class UsedDiskSpaceCheck extends Check
{
    protected int $warningThreshold = 70;
    protected int $errorThreshold = 90;
    protected ?string $filesystemName = null;

    public function setFilesystemName(string $filesystemName): self
    {
        $this->filesystemName = $filesystemName;
        return $this;
    }

    public function warnWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->warningThreshold = $percentage;
        return $this;
    }

    public function failWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->errorThreshold = $percentage;
        return $this;
    }

    public function run(): Result
    {
        $diskSpaceUsedPercentage = $this->getDiskUsagePercentage();

        $result = Result::make()->meta(['disk_space_used_percentage' => $diskSpaceUsedPercentage]);

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result->failed("Disk space is critically low: {$diskSpaceUsedPercentage}% used.");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result->warning("Warning: Disk space usage is high: {$diskSpaceUsedPercentage}% used.");
        }

        return $result->ok("Disk space is sufficient: {$diskSpaceUsedPercentage}% used.");
    }

    protected function getDiskUsagePercentage(): int
    {
        $process = Process::fromShellCommandline('df -P '.($this->filesystemName ?: '.'));
        $process->run();
        $output = $process->getOutput();

        // Menangkap persentase penggunaan disk dari output perintah
        preg_match('/(\d+)%/', $output, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}
