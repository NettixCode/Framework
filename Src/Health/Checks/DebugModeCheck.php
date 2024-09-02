<?php

namespace Nettixcode\Framework\Health\Checks;

use Nettixcode\Framework\Health\Check;
use Nettixcode\Framework\Health\Result;

class DebugModeCheck extends Check
{
    protected bool $expected = false;

    public function expectedToBe(bool $bool): self
    {
        $this->expected = $bool;
        return $this;
    }

    public function run(): Result
    {
        $actual = config('app.app_debug');

        $result = Result::make()
            ->meta([
                'actual' => $this->convertToWord($actual),
                'expected' => $this->convertToWord($this->expected),
            ]);

        if ($this->expected === $actual) {
            return $result->ok('Debug mode is as expected.');
        } else {
            return $result->failed("The debug mode was expected to be `{$this->convertToWord($this->expected)}`, but actually was `{$this->convertToWord($actual)}`");
        }
    }

    protected function convertToWord(bool $boolean): string
    {
        return $boolean ? 'true' : 'false';
    }
}
