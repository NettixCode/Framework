<?php

namespace Nettixcode\Framework\Foundation\Manager;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use ArrayAccess;

class ConfigManager implements ArrayAccess
{
    protected $config;

    public function __construct()
    {
        $this->config = new ConfigRepository([]);
        $this->loadConfigurations();
    }

    protected function loadConfigurations()
    {
        $filesystem = new Filesystem;
        $defaultPath = __DIR__ . '/../../../../config';
        $frameworkConfigPath = __DIR__ . '/../config';

        $this->loadFiles($filesystem, $defaultPath);
        $this->loadFiles($filesystem, $frameworkConfigPath);
    }

    protected function loadFiles(Filesystem $filesystem, $path)
    {
        foreach ($filesystem->allFiles($path) as $file) {
            $this->config->set($file->getBasename('.php'), require $file->getPathname());
        }
    }

    public static function load($key, $default = null)
    {
        $cfg = new static;
        return $cfg->get($key, $default);
    }

    public function get($key, $default = null)
    {
        return $this->config->get($key, $default);
    }

    public function set($key, $value)
    {
        return $this->config->set($key, $value);
    }

    public function has($key)
    {
        return $this->config->has($key);
    }

    public function all()
    {
        return $this->config->all();
    }

    public function forget($key)
    {
        return $this->config->offsetUnset($key);
    }

    public function pull($key, $default = null)
    {
        return $this->config->pull($key, $default);
    }

    public function cache()
    {
        return $this->config->cache();
    }

    public function getDefault($key, $default = null)
    {
        return $this->config->getDefault($key, $default);
    }

    public function keys()
    {
        return array_keys($this->config->all());
    }

    // ArrayAccess methods with return types
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->forget($offset);
    }

    // __toString method
    public function __toString()
    {
        return json_encode($this->config->all());
    }
}
