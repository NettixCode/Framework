<?php

namespace Nettixcode\App\Health;

class Result
{
    protected string $status;
    protected string $message;
    protected array $meta = [];

    public static function make(): self
    {
        return new static();
    }

    public function ok(string $message): self
    {
        $this->status = 'ok';
        $this->message = $message;
        return $this;
    }

    public function warning(string $message): self
    {
        $this->status = 'warning';
        $this->message = $message;
        return $this;
    }

    public function failed(string $message): self
    {
        $this->status = 'failed';
        $this->message = $message;
        return $this;
    }

    public function meta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
