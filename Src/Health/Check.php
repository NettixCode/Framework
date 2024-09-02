<?php

namespace Nettixcode\Framework\Health;

use DateTime;
use Closure;
use Exception;

abstract class Check
{
    protected string $expression = '* * * * *';
    protected ?string $name = null;
    protected ?string $label = null;
    protected array $shouldRun = [];

    public function __construct() {}

    public static function new(): static
    {
        return app(static::class);
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->getName();
    }

    public function getName(): string
    {
        return $this->name ?? (new \ReflectionClass($this))->getShortName();
    }

    public function getRunConditions(): array
    {
        return $this->shouldRun;
    }

    public function shouldRun(): bool
    {
        foreach ($this->shouldRun as $condition) {
            if (is_callable($condition) ? !$condition() : !$condition) {
                return false;
            }
        }

        $date = new DateTime();
        return $this->isDue($date);
    }

    public function if(bool|callable $condition): static
    {
        $this->shouldRun[] = $condition;
        return $this;
    }

    public function unless(bool|callable $condition): static
    {
        $this->shouldRun[] = is_callable($condition)
            ? fn() => ! $condition()
            : ! $condition;

        return $this;
    }

    abstract public function run(): Result;

    protected function isDue(DateTime $date): bool
    {
        // Implement basic cron expression logic
        // You can use a library or custom logic here
        return true; // Placeholder
    }

    public function markAsCrashed(): Result
    {
        return new Result(Status::CRASHED);
    }

    public function onTerminate(mixed $request, mixed $response): void {}

    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        $vars['shouldRun'] = array_map(
            fn($condition) => $condition instanceof Closure ? serialize($condition) : $condition,
            $vars['shouldRun']
        );
        return $vars;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            if ($property === 'shouldRun') {
                $this->shouldRun = array_map(
                    fn($condition) => is_string($condition) ? unserialize($condition) : $condition,
                    $value
                );
            } else {
                $this->$property = $value;
            }
        }
    }
}
