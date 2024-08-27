<?php

namespace Nettixcode\Framework\Foundation\Configuration;

use Closure;
use Nettixcode\Framework\Exceptions\Handler;

class Exceptions
{
    protected $handler;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public function report(callable $using)
    {
        return $this->handler->reportable($using);
    }

    public function reportable(callable $reportUsing)
    {
        return $this->handler->reportable($reportUsing);
    }

    public function render(callable $using)
    {
        $this->handler->renderable($using);

        return $this;
    }

    public function renderable(callable $renderUsing)
    {
        $this->handler->renderable($renderUsing);

        return $this;
    }

    public function dontReport(array|string $class)
    {
        foreach ((array) $class as $exceptionClass) {
            $this->handler->dontReport($exceptionClass);
        }

        return $this;
    }

    public function dontFlash(array|string $attributes)
    {
        $this->handler->dontFlash((array) $attributes);

        return $this;
    }
}
