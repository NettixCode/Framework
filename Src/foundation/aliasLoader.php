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
            $aliases = $this->aliases[$alias];

            if (is_array($aliases)) {
                foreach ($aliases as $class) {
                    class_alias($class, $alias);
                }
            } elseif (is_string($aliases)) {
                class_alias($aliases, $alias);
            }
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
