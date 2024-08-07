<?php

namespace Nettixcode\Framework\Foundation;

class AliasLoader
{
    protected $aliases = [];

    public function __construct(array $aliases)
    {
        $this->aliases = $aliases;
    }

    public static function getInstance(array $aliases = [])
    {
        static $instance;

        if (is_null($instance)) {
            $instance = new static($aliases);
        } elseif ($aliases) {
            $instance->merge($aliases);
        }

        return $instance;
    }

    public function load($alias)
    {
        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }

    public function register()
    {
        spl_autoload_register([$this, 'load']);
    }

    public function merge(array $aliases)
    {
        $this->aliases = array_merge($this->aliases, $aliases);
    }
}
