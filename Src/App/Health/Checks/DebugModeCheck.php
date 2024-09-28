<?php

namespace Nettixcode\App\Health\Checks;

use Nettixcode\App\Health\Check;
use Nettixcode\App\Health\Result;

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
        $actual = config('app.debug');

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
